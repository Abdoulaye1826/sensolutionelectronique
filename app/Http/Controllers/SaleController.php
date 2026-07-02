<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly WhatsAppService $whatsAppService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'sale_type', 'customer_id']);
        $sales = $this->saleService->paginate($filters);

        return view('sales.index', compact('sales', 'filters'));
    }

    public function create(): View
    {
        return view('sales.create', [
            'customers' => $this->saleService->getCustomers(),
            'products' => $this->saleService->getProducts(),
            'categories' => $this->saleService->getCategories(),
            'sale' => null,
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        try {
            $this->saleService->create($request->validated(), auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.index')
            ->with('success', 'Vente créée avec succès.');
    }

    public function edit(Sale $sale): View
    {
        return view('sales.edit', [
            'sale' => $sale,
            'customers' => $this->saleService->getCustomers(),
            'products' => $this->saleService->getProducts(),
            'categories' => $this->saleService->getCategories(),
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        try {
            $this->saleService->update($sale, $request->validated(), auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.index')
            ->with('success', 'Vente mise à jour avec succès.');
    }

        public function destroy(Sale $sale): RedirectResponse
    {
        try {
            $this->saleService->delete($sale);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales.index')
            ->with('success', 'Vente supprimée avec succès.');
    }

    /**
     * Recherche de produits pour l'autocomplétion du module d'échange.
     */
    /**
     * Recherche de clients pour l'autocomplétion du formulaire de vente
     * (nom, téléphone, email).
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $term = $request->input('q', '');

        $customers = Customer::query()
            ->search($term)
            ->orderBy('full_name')
            ->limit(15)
            ->get(['id', 'full_name', 'phone', 'email']);

        return response()->json($customers);
    }

    public function searchExchangeProducts(Request $request): JsonResponse
    {
        $term = $request->input('q', '');

        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('reference', 'like', "%{$term}%")
                    ->orWhere('brand', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'reference', 'name', 'brand', 'sale_price', 'category_id', 'tracks_imei']);

        return response()->json($products);
    }

    /**
     * Création rapide d'un produit depuis la modale d'échange.
     */
    public function storeExchangeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'reference' => ['nullable', 'string', 'max:50', 'unique:products,reference'],
            'brand' => ['nullable', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            '_modal_imei' => ['nullable', 'string', 'max:20'],
        ]);

        // Un IMEI saisi dans la modale signifie que le produit apporté est un
        // téléphone : le produit créé doit donc être suivi par IMEI, sinon
        // SaleService::buildExchangeDetails() et receiveExchangeImei()
        // ignorent silencieusement l'IMEI (ni affiché sur la facture, ni
        // enregistré en stock).
        $imei = trim((string) ($validated['_modal_imei'] ?? ''));
        unset($validated['_modal_imei']);
        $validated['tracks_imei'] = $imei !== '';

        if (empty($validated['reference'])) {
            $reference = Str::upper('EX-' . Str::random(6));
            while (Product::where('reference', $reference)->exists()) {
                $reference = Str::upper('EX-' . Str::random(6));
            }
            $validated['reference'] = $reference;
        }

        $validated['purchase_price'] = $validated['purchase_price'] ?? 0;
        // Le stock est mis à 0 ici : il sera incrémenté par SaleService::applyStockChanges()
        // lors de la validation de l'échange, pour éviter un double comptage.
        $validated['stock_quantity'] = 0;
        $validated['minimum_stock'] = 5;
        $validated['is_active'] = true;

        $product = Product::create($validated);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'reference' => $product->reference,
            'brand' => $product->brand,
            'sale_price' => $product->sale_price,
            'category_id' => $product->category_id,
            'stock_quantity' => $product->stock_quantity,
            'tracks_imei' => $product->tracks_imei,
        ], 201);
    }

    /**
     * Affiche le bon d'échange imprimable d'une vente de type échange.
     */
    public function printExchangeVoucher(Sale $sale): View
    {
        abort_unless($sale->isEchange(), 404);

        $sale->load(['customer', 'user', 'items.product', 'items.productImei', 'invoice.payments']);
        $invoice = $sale->invoice;
        $downloadUrl = route('sales.exchange-voucher.download', $sale);

        return view('documents.sale_document', compact('sale', 'invoice', 'downloadUrl'));
    }

    /**
     * Télécharge le bon d'échange en PDF.
     */
    public function downloadExchangeVoucher(Sale $sale): Response
    {
        abort_unless($sale->isEchange(), 404);

        $content = $this->saleService->renderExchangeVoucherPdfContent($sale);
        $fileName = "{$sale->exchange_voucher_number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Version publique (lien signé, sans authentification) du PDF du bon
     * d'échange — utilisée par le lien partagé sur WhatsApp pour que le
     * client puisse ouvrir directement le document sans être connecté à
     * l'application.
     */
    public function publicExchangeVoucherPdf(Sale $sale): Response
    {
        abort_unless($sale->isEchange(), 404);

        $content = $this->saleService->renderExchangeVoucherPdfContent($sale);
        $fileName = "{$sale->exchange_voucher_number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf; charset=UTF-8',
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Envoie le bon d'échange au client via WhatsApp.
     */
    public function sendExchangeVoucherWhatsApp(Sale $sale): RedirectResponse
    {
        $built = $this->buildExchangeVoucherWhatsAppPayload($sale);

        if ($built['waUrl'] === null) {
            return back()->with('error', "Le client n'a pas de numéro WhatsApp valide (format sénégalais +221 attendu).");
        }

        return redirect()->away($built['waUrl']);
    }

    /**
     * Retourne le message, le lien wa.me et l'URL du PDF réel du bon
     * d'échange, utilisé par le bouton de partage WhatsApp côté navigateur
     * pour tenter un partage natif du fichier PDF.
     */
    public function exchangeVoucherWhatsAppPayload(Sale $sale): JsonResponse
    {
        $built = $this->buildExchangeVoucherWhatsAppPayload($sale);

        if ($built['waUrl'] === null) {
            return response()->json([
                'error' => "Le client n'a pas de numéro WhatsApp valide (format sénégalais +221 attendu).",
            ], 422);
        }

        return response()->json($built);
    }

    private function buildExchangeVoucherWhatsAppPayload(Sale $sale): array
    {
        abort_unless($sale->isEchange(), 404);

        $sale->load(['customer', 'invoice.payments']);

        // Lien signé public (sans authentification) : le client doit pouvoir
        // ouvrir le PDF directement depuis WhatsApp sans être connecté à
        // l'application — la route de téléchargement classique exige une
        // session authentifiée et redirigeait le client vers la page de
        // connexion.
        $pdfUrl = URL::signedRoute('sales.exchange-voucher.public-pdf', ['sale' => $sale->id]);
        $message = $this->whatsAppService->buildMessage($sale, "bon d'échange", $sale->exchange_voucher_number, $pdfUrl, $sale->invoice);
        $waUrl = $this->whatsAppService->buildLink($sale->customer?->phone, $message);

        return [
            'message' => $message,
            'pdfUrl' => $pdfUrl,
            'waUrl' => $waUrl,
            'fileName' => $sale->exchange_voucher_number . '.pdf',
        ];
    }
}
