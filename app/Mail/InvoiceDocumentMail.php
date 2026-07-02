<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Sale $sale,
        public readonly string $documentLabel,
        public readonly string $documentNumber,
        public readonly string $pdfContent,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre {$this->documentLabel} {$this->documentNumber} — " . config('company.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-document',
            with: [
                'invoice' => $this->invoice,
                'sale' => $this->sale,
                'documentLabel' => $this->documentLabel,
                'documentNumber' => $this->documentNumber,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "{$this->documentNumber}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
