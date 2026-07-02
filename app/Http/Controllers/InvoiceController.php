<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceDocumentMail;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
        $invoice->load(['payments' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('id'), 'payments.recordedBy']);

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
        $invoice->load(['sale.customer', 'sale.user', 'sale.items.product', 'sale.items.productImei', 'payments']);
        $sale = $invoice->sale;
        abort_if($sale === null, 404);
        $downloadUrl = route('invoices.download', $invoice);

        return view('documents.sale_document', compact('sale', 'invoice', 'downloadUrl'));
    }

    public function download(Invoice $invoice): Response
    {
        $content = $this->invoiceService->renderPdfContent($invoice);
        $fileName = "{$invoice->invoice_number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Version publique (lien signé, sans authentification) de la facture —
     * utilisée par le lien partagé sur WhatsApp pour que le client puisse
     * ouvrir directement le document sans être connecté à l'application.
     */
    public function publicPdf(Invoice $invoice): Response
    {
        $content = $this->invoiceService->renderPdfContent($invoice);
        $fileName = "{$invoice->invoice_number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf; charset=UTF-8',
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
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

    public function sendEmail(Invoice $invoice): RedirectResponse
    {
        $invoice->load(['sale.customer', 'customer']);
        $sale = $invoice->sale;
        abort_if($sale === null, 404);

        $email = $invoice->customer?->email ?? $sale->customer?->email;
        if (empty($email)) {
            return back()->with('error', "Le client n'a pas d'adresse email renseignée.");
        }

        $isEchange = $sale->isEchange();
        $documentLabel = $isEchange ? "bon d'échange" : 'facture';
        $documentNumber = $isEchange ? $sale->exchange_voucher_number : $invoice->invoice_number;
        $pdfContent = $this->invoiceService->renderPdfContent($invoice);

        Mail::to($email)->send(new InvoiceDocumentMail($invoice, $sale, $documentLabel, $documentNumber, $pdfContent));

        return back()->with('success', "Document envoyé par email à {$email}.");
    }

    private function buildWhatsAppPayload(Invoice $invoice): array
    {
        $invoice->load(['sale.customer', 'payments']);
        $sale = $invoice->sale;
        abort_if($sale === null, 404);

        $isEchange = $sale->isEchange();
        $documentLabel = $isEchange ? "bon d'échange" : 'facture';
        $documentNumber = $isEchange ? $sale->exchange_voucher_number : $invoice->invoice_number;

        // Lien signé public (sans authentification) : le client doit pouvoir
        // ouvrir le PDF directement depuis WhatsApp sans être connecté à
        // l'application — la route de téléchargement classique exige une
        // session authentifiée et redirigeait le client vers la page de
        // connexion (il recevait donc toute l'appli au lieu du seul document).
        $pdfUrl = $isEchange
            ? URL::signedRoute('sales.exchange-voucher.public-pdf', ['sale' => $sale->id])
            : URL::signedRoute('invoices.public-pdf', ['invoice' => $invoice->id]);

        $message = $this->whatsAppService->buildMessage($sale, $documentLabel, $documentNumber, $pdfUrl, $invoice);
        $waUrl = $this->whatsAppService->buildLink($sale->customer?->phone, $message);

        return [
            'message' => $message,
            'pdfUrl' => $pdfUrl,
            'waUrl' => $waUrl,
            'fileName' => $documentNumber . '.pdf',
        ];
    }
}
