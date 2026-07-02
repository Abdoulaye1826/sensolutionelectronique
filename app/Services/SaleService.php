<?php


namespace App\Services;

use App\Enums\ImeiStatus;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Enums\StockMovementType;
use App\Enums\WarrantyDuration;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductImei;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly InvoiceService $invoiceService,
        private readonly PaymentService $paymentService
    ) {
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Sale::query()
            ->with(['customer', 'user'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('sale_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['sale_type'] ?? null, function ($query, $saleType) {
                $query->where('sale_type', $saleType);
            })
            ->when($filters['customer_id'] ?? null, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getCustomers()
    {
        return Customer::orderBy('full_name')->get();
    }

    public function getProducts()
    {
        return Product::active()->orderBy('name')->get();
    }

    public function getCategories()
    {
        return Category::active()->orderBy('name')->get();
    }

    public function create(array $data, int $userId): Sale
    {
        $saleType = isset($data['sale_type']) ? SaleType::from($data['sale_type']) : SaleType::Vente;
        $data['sale_type'] = $saleType;
        $data['sale_number'] = $this->generateSaleNumber($saleType);
        $data['user_id'] = $userId;
        $data['status'] = $data['status'] ?? SaleStatus::Draft;
        $data['sale_date'] = now()->toDateString();
        $data['sold_at'] = now();
        $data['exchange_details'] = null;
        $data['exchange_voucher_number'] = null;
        $data['warranty_end_date'] = WarrantyDuration::from($data['warranty_duration'] ?? 'none')
            ->endDateFrom(\Carbon\Carbon::parse($data['sale_date']));

        $paymentMethod = $data['payment_method'] ?? null;
        $amountGiven = isset($data['amount_given']) && $data['amount_given'] !== '' ? (float) $data['amount_given'] : null;

        return DB::transaction(function () use ($data, $saleType, $userId, $paymentMethod, $amountGiven) {
            if ($saleType === SaleType::Echange) {
                $exchangeProduct = $this->resolveExchangeProduct($data);
                if ($exchangeProduct !== null) {
                    $data['exchange_details'] = $this->buildExchangeDetails($data, $exchangeProduct);
                }
                $data['exchange_voucher_number'] = $this->generateExchangeVoucherNumber();
            }

            $saleTotals = $this->calculateTotals($data);
            $data['subtotal_ht'] = 0;
            $data['total_ttc'] = $saleTotals['total'];

            $sale = Sale::create($data);
            foreach ($this->buildSaleItems($data) as $itemData) {
                $sale->items()->create([
                    'product_id' => $itemData['product_id'],
                    'product_imei_id' => $this->resolveItemImei($itemData)?->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => 0,
                    'line_total' => $itemData['line_total'],
                ]);
            }

            $this->activityLog->log('create', $sale, "{$saleType->label()} créée : {$sale->sale_number}");

            if ($sale->status === SaleStatus::Validated) {
                $this->applyStockChanges($sale);
                $invoice = $this->invoiceService->createFromSale($sale);
                $this->recordInitialPayment($invoice, $paymentMethod, $amountGiven, $userId);
            }

            return $sale;
        });
    }

    /**
     * Enregistre le paiement initial de la facture au mode choisi lors de la
     * saisie de la vente (Wave, Orange Money, Espèces). Le montant donné par
     * le client détermine si le paiement est intégral ou partiel : à défaut
     * de montant saisi, on suppose un paiement intégral du total (montant
     * plafonné au total de la facture). Le statut "partiel" de la facture
     * est ensuite recalculé automatiquement par PaymentService.
     * Ne fait rien si aucun mode n'a été choisi, si rien n'est dû, si le
     * montant donné est nul, ou si un paiement existe déjà pour cette
     * facture (pour éviter les doublons lors d'une modification ultérieure).
     */
    private function recordInitialPayment(?Invoice $invoice, ?string $paymentMethod, ?float $amountGiven, int $userId): void
    {
        if ($paymentMethod === null || $invoice === null) {
            return;
        }

        if ((float) $invoice->total_ttc <= 0 || $invoice->payments()->exists()) {
            return;
        }

        $amount = $amountGiven !== null
            ? min(max(0, $amountGiven), (float) $invoice->total_ttc)
            : (float) $invoice->total_ttc;

        if ($amount <= 0) {
            return;
        }

        $this->paymentService->store($invoice, [
            'amount' => $amount,
            'method' => $paymentMethod,
            'paid_at' => now()->toDateString(),
        ], $userId);
    }

    public function update(Sale $sale, array $data, int $userId): Sale
    {
        $previousStatus = $sale->status;
        $saleType = isset($data['sale_type']) ? SaleType::from($data['sale_type']) : $sale->sale_type;
        $data['sale_type'] = $saleType;
        $paymentMethod = $data['payment_method'] ?? null;
        $amountGiven = isset($data['amount_given']) && $data['amount_given'] !== '' ? (float) $data['amount_given'] : null;
        $data['warranty_end_date'] = WarrantyDuration::from($data['warranty_duration'] ?? 'none')
            ->endDateFrom($sale->sale_date);

        if ($saleType === SaleType::Echange) {
            $exchangeProduct = $this->resolveExchangeProduct($data);
            if ($exchangeProduct !== null) {
                $data['exchange_details'] = $this->buildExchangeDetails($data, $exchangeProduct);
                $data['exchange_voucher_number'] = $sale->exchange_voucher_number ?? $this->generateExchangeVoucherNumber();
            }
        } else {
            $data['exchange_details'] = null;
            $data['exchange_voucher_number'] = null;
        }

        return DB::transaction(function () use ($sale, $data, $previousStatus, $userId, $paymentMethod, $amountGiven) {
            if (Schema::hasColumn('sale_items', 'returned_at') && $sale->items()->whereNotNull('returned_at')->exists()) {
                throw new \RuntimeException('Impossible de modifier une vente dont un produit a déjà été retourné.');
            }

            if ($previousStatus === SaleStatus::Validated) {
                $this->reverseStockChanges($sale);
            }

            $saleTotals = $this->calculateTotals($data);
            $data['subtotal_ht'] = 0;
            $data['total_ttc'] = $saleTotals['total'];

            $sale->update($data);

            $sale->items()->delete();
            foreach ($this->buildSaleItems($data) as $itemData) {
                $sale->items()->create([
                    'product_id' => $itemData['product_id'],
                    'product_imei_id' => $this->resolveItemImei($itemData)?->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => 0,
                    'line_total' => $itemData['line_total'],
                ]);
            }

            $this->activityLog->log('update', $sale, "Vente mise à jour : {$sale->sale_number}");

            if ($sale->status === SaleStatus::Validated) {
                $this->applyStockChanges($sale);

                $invoice = $sale->invoice;
                if ($invoice === null) {
                    $invoice = $this->invoiceService->createFromSale($sale);
                } else {
                    // La facture existe déjà : on resynchronise ses montants sur
                    // ceux de la vente (ex: montant ajouté modifié lors d'un échange).
                    $invoice = $this->invoiceService->update($invoice, [
                        'subtotal_ht' => $sale->subtotal_ht,
                        'total_ttc' => $sale->total_ttc,
                    ]);
                }

                $this->recordInitialPayment($invoice, $paymentMethod, $amountGiven, $userId);
            }

            return $sale->fresh();
        });
    }

    /**
     * Génère le PDF du bon d'échange, utilisé à la fois par le téléchargement
     * direct et par le lien public signé partagé sur WhatsApp — un seul
     * endroit pour la configuration dompdf (même pattern qu'InvoiceService::renderPdfContent()).
     */
    public function renderExchangeVoucherPdfContent(Sale $sale): string
    {
        $sale->load(['customer', 'user', 'items.product', 'items.productImei', 'invoice.payments']);
        $invoice = $sale->invoice;
        $downloadUrl = null;

        $pdf = PDF::loadView('documents.sale_document', compact('sale', 'invoice', 'downloadUrl'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true);

        return $pdf->output();
    }

    public function delete(Sale $sale): void
    {
        if ($sale->status === SaleStatus::Validated) {
            throw new \RuntimeException('Impossible de supprimer une vente déjà validée.');
        }

        $saleNumber = $sale->sale_number;
        $sale->delete();

        $this->activityLog->log('delete', null, "Vente supprimée : {$saleNumber}");
    }

    private function reverseStockChanges(Sale $sale): void
    {
        $movements = StockMovement::query()
            ->where('reference', $sale->sale_number)
            ->get();

        foreach ($movements as $movement) {
            $product = $movement->product;
            if ($product === null) {
                $movement->delete();
                continue;
            }

            if ($product->tracks_imei) {
                // Le stock des produits suivis par IMEI n'est jamais ajusté
                // par une simple arithmétique : on remet les IMEI concernés
                // dans leur état d'origine, puis on recalcule le stock.
                if ($movement->type === StockMovementType::Sale) {
                    ProductImei::where('product_id', $product->id)
                        ->where('sale_id', $sale->id)
                        ->update(['status' => ImeiStatus::Available->value, 'sale_id' => null, 'sold_at' => null]);
                } elseif ($movement->type === StockMovementType::Return) {
                    // Le téléphone avait été ajouté au stock via cet échange :
                    // annuler l'échange retire ce téléphone du catalogue.
                    ProductImei::where('product_id', $product->id)
                        ->where('exchange_sale_id', $sale->id)
                        ->delete();
                }

                $movement->delete();
                $product->syncImeiStock();
                continue;
            }

            $quantityBefore = $product->stock_quantity;
            $quantity = $movement->quantity;

            if ($movement->type === StockMovementType::Sale) {
                $quantityAfter = $quantityBefore + $quantity;
            } elseif ($movement->type === StockMovementType::Return) {
                $quantityAfter = max(0, $quantityBefore - $quantity);
            } else {
                $quantityAfter = $quantityBefore;
            }

            $product->update(['stock_quantity' => $quantityAfter]);
            $movement->delete();
        }
    }

    private function applyStockChanges(Sale $sale): void
    {
        $sale->loadMissing('items.product', 'items.productImei');

        foreach ($sale->items as $item) {
            $product = $item->product;
            if ($product === null) {
                continue;
            }

            if ($product->tracks_imei) {
                $this->sellItemImei($sale, $product, $item);
                continue;
            }

            $quantityBefore = $product->stock_quantity;
            $quantity = $item->quantity;
            $quantityAfter = max(0, $quantityBefore - $quantity);
            $reason = $sale->isVente() ? 'Vente validée' : 'Échange - produit vendu';

            $product->update(['stock_quantity' => $quantityAfter]);
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $sale->user_id,
                'type' => StockMovementType::Sale,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $reason,
                'reference' => $sale->sale_number,
            ]);
        }

        if ($sale->isEchange() && isset($sale->exchange_details['product_id'], $sale->exchange_details['quantity'])) {
            $returnProduct = Product::find($sale->exchange_details['product_id']);
            if ($returnProduct !== null && $returnProduct->tracks_imei) {
                $this->receiveExchangeImei($sale, $returnProduct);
            } elseif ($returnProduct !== null) {
                $quantityBefore = $returnProduct->stock_quantity;
                $quantity = (int) $sale->exchange_details['quantity'];
                $quantityAfter = $quantityBefore + $quantity;

                $returnProduct->update(['stock_quantity' => $quantityAfter]);
                StockMovement::create([
                    'product_id' => $returnProduct->id,
                    'user_id' => $sale->user_id,
                    'type' => StockMovementType::Return,
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reason' => 'Échange validé',
                    'reference' => $sale->sale_number,
                ]);
            }
        }
    }

    /**
     * Marque l'IMEI rattaché à une ligne de vente comme vendu et resynchronise
     * le stock du produit à partir du nombre d'IMEI restant disponibles.
     */
    private function sellItemImei(Sale $sale, Product $product, SaleItem $item): void
    {
        $imei = $item->productImei;

        if ($imei === null || $imei->status === ImeiStatus::Sold) {
            throw new \RuntimeException("L'IMEI sélectionné pour {$product->name} n'est plus disponible.");
        }

        $quantityBefore = $product->stock_quantity;
        $imei->update(['status' => ImeiStatus::Sold->value, 'sale_id' => $sale->id, 'sold_at' => now()]);
        $product->syncImeiStock();

        $reason = ($sale->isVente() ? 'Vente validée' : 'Échange - produit vendu') . " (IMEI {$imei->imei})";

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $sale->user_id,
            'type' => StockMovementType::Sale,
            'quantity' => 1,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $product->fresh()->stock_quantity,
            'reason' => $reason,
            'reference' => $sale->sale_number,
        ]);
    }

    /**
     * Ajoute au stock le téléphone apporté par le client lors d'un échange,
     * identifié par son IMEI (saisi ou scanné), et le marque disponible.
     */
    private function receiveExchangeImei(Sale $sale, Product $product): void
    {
        $imeiValue = trim((string) ($sale->exchange_details['imei'] ?? ''));

        if ($imeiValue === '') {
            throw new \RuntimeException("L'IMEI du téléphone apporté par le client est obligatoire pour {$product->name}.");
        }

        if (ProductImei::where('imei', $imeiValue)->exists()) {
            throw new \RuntimeException("L'IMEI {$imeiValue} est déjà enregistré dans le système.");
        }

        $quantityBefore = $product->stock_quantity;

        ProductImei::create([
            'product_id' => $product->id,
            'imei' => $imeiValue,
            'status' => ImeiStatus::Available,
            'exchange_sale_id' => $sale->id,
        ]);

        $product->syncImeiStock();

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $sale->user_id,
            'type' => StockMovementType::Return,
            'quantity' => 1,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $product->fresh()->stock_quantity,
            'reason' => "Échange validé (IMEI {$imeiValue} reçu)",
            'reference' => $sale->sale_number,
        ]);
    }

        private function resolveExchangeProduct(array $data): ?Product
    {
        if (! isset($data['sale_type']) || $data['sale_type'] !== SaleType::Echange) {
            return null;
        }

        if (! empty($data['exchange_product_id'])) {
            return Product::find($data['exchange_product_id']);
        }

        return null;
    }

    private function buildExchangeDetails(array $data, Product $product): array
    {
        // On se base sur l'IMEI réellement saisi plutôt que sur le seul
        // indicateur tracks_imei du produit : un produit d'échange créé à la
        // volée (modale "Ajouter un produit") peut ne pas avoir cet
        // indicateur correctement positionné, ce qui faisait disparaître
        // silencieusement l'IMEI saisi de la facture d'échange.
        $imei = trim((string) ($data['exchange_imei'] ?? ''));
        $tracksImei = $product->tracks_imei || $imei !== '';

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'brand' => $product->brand,
            'description' => $product->description,
            'category_id' => $product->category_id,
            'quantity' => $tracksImei ? 1 : ($data['exchange_quantity'] ?? 0),
            'added_amount' => $data['exchange_added_amount'] ?? 0,
            'imei' => $imei !== '' ? $imei : null,
        ];
    }

    /**
     * Numéro d'échange continu (BEX-000001, BEX-000002, ...), jamais
     * réinitialisé par jour.
     */
    private function generateExchangeVoucherNumber(): string
    {
        $next = $this->nextContinuousNumber(Sale::query(), 'exchange_voucher_number');

        return sprintf('BEX-%06d', $next);
    }

    /**
     * Numéro de vente continu (V-000001, E-000001, ...), partagé entre
     * ventes et échanges pour garantir une suite ininterrompue, jamais
     * réinitialisée par jour.
     */
    private function generateSaleNumber(SaleType $type): string
    {
        $prefix = $type === SaleType::Vente ? 'V' : 'E';
        $next = $this->nextContinuousNumber(Sale::query(), 'sale_number');

        return sprintf('%s-%06d', $prefix, $next);
    }

    /**
     * Détermine le prochain numéro d'une séquence continue à partir de la
     * plus grande valeur numérique déjà utilisée dans la colonne donnée
     * (quel que soit son préfixe), pour ne jamais réutiliser ou réinitialiser
     * un numéro même si des enregistrements ont été supprimés.
     */
    private function nextContinuousNumber($query, string $column): int
    {
        $max = $query->get([$column])
            ->pluck($column)
            ->filter()
            ->map(function ($value) {
                // Ne retient que le suffixe numérique final (ex: "0002" dans
                // "V-20260629-0002" ou "000002" dans "V-000002"), jamais une
                // éventuelle date intercalée dans l'ancien format.
                preg_match('/(\d+)$/', $value, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            })
            ->max();

        return ((int) $max) + 1;
    }

    private function buildSaleItems(array $data): array
    {
        $productIds = Arr::wrap($data['product_id'] ?? []);
        $quantities = Arr::wrap($data['quantity'] ?? []);
        $unitPrices = Arr::wrap($data['unit_price'] ?? []);
        $imeis = Arr::wrap($data['imei'] ?? []);

        $items = [];
        foreach ($productIds as $index => $productId) {
            if (empty($productId)) {
                continue;
            }

            $product = Product::find($productId);
            $tracksImei = (bool) $product?->tracks_imei;

            $quantity = isset($quantities[$index]) ? (int) $quantities[$index] : 1;
            $unitPrice = isset($unitPrices[$index]) ? (float) $unitPrices[$index] : 0;
            // Un IMEI = un appareil : la quantité est toujours 1 pour ces produits.
            $quantity = $tracksImei ? 1 : max(1, $quantity);
            $lineTotal = round($quantity * max(0, $unitPrice), 2);

            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'tracks_imei' => $tracksImei,
                'imei' => $tracksImei ? trim((string) ($imeis[$index] ?? '')) : null,
            ];
        }

        return $items;
    }

    /**
     * Résout l'IMEI saisi/scanné pour une ligne de vente vers l'unité
     * disponible correspondante. Lève une exception claire en cas d'IMEI
     * manquant, inconnu ou déjà vendu — jamais d'incohérence silencieuse.
     */
    private function resolveItemImei(array $itemData): ?ProductImei
    {
        if (empty($itemData['tracks_imei'])) {
            return null;
        }

        $imeiValue = $itemData['imei'] ?? null;
        if (empty($imeiValue)) {
            throw new \RuntimeException('Veuillez saisir ou scanner un IMEI pour ce produit.');
        }

        $imei = ProductImei::where('product_id', $itemData['product_id'])
            ->where('imei', $imeiValue)
            ->first();

        if ($imei === null) {
            throw new \RuntimeException("L'IMEI {$imeiValue} n'est pas enregistré pour ce produit.");
        }

        if ($imei->status === ImeiStatus::Sold) {
            throw new \RuntimeException("L'IMEI {$imeiValue} a déjà été vendu.");
        }

        return $imei;
    }

    private function calculateTotals(array $data): array
    {
        $saleType = $data['sale_type'] ?? null;

        if ($saleType === SaleType::Echange) {
            // Aucun calcul automatique pour les échanges : le montant ajouté
            // par le client est saisi manuellement et utilisé tel quel.
            return [
                'subtotal' => 0,
                'tax' => 0,
                'total' => (float) ($data['exchange_added_amount'] ?? 0),
            ];
        }

        $items = $this->buildSaleItems($data);
        $total = array_sum(array_column($items, 'line_total'));
        $discount = isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0;

        return [
            'subtotal' => 0,
            'tax' => 0,
            'total' => max(0, $total - $discount),
        ];
    }
}
