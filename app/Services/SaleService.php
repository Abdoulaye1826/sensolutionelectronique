<?php


namespace App\Services;

use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\InvoiceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly InvoiceService $invoiceService
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
            ->orderByDesc('sale_date')
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

        return DB::transaction(function () use ($data, $saleType) {
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
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => 0,
                    'line_total' => $itemData['line_total'],
                ]);
            }

            $this->activityLog->log('create', $sale, "{$saleType->label()} créée : {$sale->sale_number}");

            if ($sale->status === SaleStatus::Validated) {
                $this->applyStockChanges($sale);
                $this->invoiceService->createFromSale($sale);
            }

            return $sale;
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        $previousStatus = $sale->status;
        $saleType = isset($data['sale_type']) ? SaleType::from($data['sale_type']) : $sale->sale_type;
        $data['sale_type'] = $saleType;

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

        return DB::transaction(function () use ($sale, $data, $previousStatus) {
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
                    $this->invoiceService->createFromSale($sale);
                } else {
                    // La facture existe déjà : on resynchronise ses montants sur
                    // ceux de la vente (ex: montant ajouté modifié lors d'un échange).
                    $this->invoiceService->update($invoice, [
                        'subtotal_ht' => $sale->subtotal_ht,
                        'total_ttc' => $sale->total_ttc,
                    ]);
                }
            }

            return $sale->fresh();
        });
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
        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            $product = $item->product;
            if ($product === null) {
                continue;
            }

            $quantityBefore = $product->stock_quantity;
            $quantity = $item->quantity;
            $quantityAfter = max(0, $quantityBefore - $quantity);
            $movementType = StockMovementType::Sale;
            $reason = $sale->isVente() ? 'Vente validée' : 'Échange - produit vendu';

            $product->update(['stock_quantity' => $quantityAfter]);
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $sale->user_id,
                'type' => $movementType,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $reason,
                'reference' => $sale->sale_number,
            ]);
        }

        if ($sale->isEchange() && isset($sale->exchange_details['product_id'], $sale->exchange_details['quantity'])) {
            $returnProduct = Product::find($sale->exchange_details['product_id']);
            if ($returnProduct !== null) {
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
        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'brand' => $product->brand,
            'description' => $product->description,
            'category_id' => $product->category_id,
            'quantity' => $data['exchange_quantity'] ?? 0,
            'added_amount' => $data['exchange_added_amount'] ?? 0,
        ];
    }

    private function generateExchangeVoucherNumber(): string
    {
        $date = now()->format('Ymd');
        $count = Sale::where('exchange_voucher_number', 'like', "BEX-{$date}-%")->count() + 1;

        return sprintf('BEX-%s-%04d', $date, $count);
    }

    private function generateSaleNumber(SaleType $type): string
    {
        $prefix = $type === SaleType::Vente ? 'V' : 'E';
        $date = now()->format('Ymd');
        $count = Sale::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $count);
    }

    private function buildSaleItems(array $data): array
    {
        $productIds = Arr::wrap($data['product_id'] ?? []);
        $quantities = Arr::wrap($data['quantity'] ?? []);
        $unitPrices = Arr::wrap($data['unit_price'] ?? []);

        $items = [];
        foreach ($productIds as $index => $productId) {
            if (empty($productId)) {
                continue;
            }

            $quantity = isset($quantities[$index]) ? (int) $quantities[$index] : 1;
            $unitPrice = isset($unitPrices[$index]) ? (float) $unitPrices[$index] : 0;
            $lineTotal = round(max(0, $quantity) * max(0, $unitPrice), 2);

            $items[] = [
                'product_id' => $productId,
                'quantity' => max(1, $quantity),
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        return $items;
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
