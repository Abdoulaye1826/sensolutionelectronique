<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly WhatsAppService $whatsAppService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'customer_id']);
        $invoices = $this->invoiceService->paginate($filters);
        $summary = $this->invoiceService->summary();

        return view('invoices.index', compact('invoices', 'filters', 'summary'));
    }

    public function create(): View
    {
        return view('invoices.create', [
            'customers' => $this->invoiceService->getCustomers(),
            'sales' => $this->invoiceService->getAvailableSales(),
            'invoice' => null,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->invoiceService->create($request->validated());

        return redirect()->route('invoices.index')
            ->with('success', 'Facture créée avec succès.');
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.edit', [
            'invoice' => $invoice,
            'customers' => $this->invoiceService->getCustomers(),
            'sales' => $this->invoiceService->getAvailableSales($invoice->sale),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->update($invoice, $request->validated());

        return redirect()->route('invoices.index')
            ->with('success', 'Facture mise à jour avec succès.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->delete($invoice);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Facture supprimée avec succès.');
    }

    public function print(Invoice $invoice): View
    {
        $invoice->load(['sale.customer', 'sale.user', 'sale.items.product']);
        $sale = $invoice->sale;
        abort_if($sale === null, 404);
        $downloadUrl = route('invoices.download', $invoice);

        return view('documents.sale_document', compact('sale', 'invoice', 'downloadUrl'));
    }

    public function download(Invoice $invoice): Response
    {
        $invoice->load(['sale.customer', 'sale.user', 'sale.items.product']);
        $sale = $invoice->sale;
        $downloadUrl = null;

        $pdf = PDF::loadView('documents.sale_document', compact('sale', 'invoice', 'downloadUrl'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true);

        $fileName = "{$invoice->invoice_number}.pdf";
        $content = $pdf->output();

        return response($content, 200, [
            'Content-Type' => 'application/pdf; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function sendWhatsApp(Invoice $invoice): RedirectResponse
    {
        $built = $this->buildWhatsAppPayload($invoice);

        if ($built['waUrl'] === null) {
            return back()->with('error', "Le client n'a pas de numéro WhatsApp valide (format sénégalais +221 attendu).");
        }

        return redirect()->away($built['waUrl']);
    }

    /**
     * Retourne le message, le lien wa.me et l'URL du PDF réel (et non un lien
     * vers l'aperçu HTML) afin que le client puisse être contacté avec le
     * document PDF effectif. Utilisé par le bouton de partage WhatsApp côté
     * navigateur, qui tente d'abord un partage natif du fichier PDF.
     */
    public function whatsAppPayload(Invoice $invoice): JsonResponse
    {
        $built = $this->buildWhatsAppPayload($invoice);

        if ($built['waUrl'] === null) {
            return response()->json([
                'error' => "Le client n'a pas de numéro WhatsApp valide (format sénégalais +221 attendu).",
            ], 422);
        }

        return response()->json($built);
    }

    private function buildWhatsAppPayload(Invoice $invoice): array
    {
        $invoice->load(['sale.customer']);
        $sale = $invoice->sale;
        abort_if($sale === null, 404);

        $isEchange = $sale->isEchange();
        $documentLabel = $isEchange ? "bon d'échange" : 'facture';
        $documentNumber = $isEchange ? $sale->exchange_voucher_number : $invoice->invoice_number;
        $pdfUrl = $isEchange
            ? route('sales.exchange-voucher.download', $sale)
            : route('invoices.download', $invoice);

        $message = $this->whatsAppService->buildMessage($sale, $documentLabel, $documentNumber, $pdfUrl);
        $waUrl = $this->whatsAppService->buildLink($sale->customer?->phone, $message);

        return [
            'message' => $message,
            'pdfUrl' => $pdfUrl,
            'waUrl' => $waUrl,
            'fileName' => $documentNumber . '.pdf',
        ];
    }
}
