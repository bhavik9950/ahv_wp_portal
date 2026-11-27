<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $company
     */
    public function __construct(
        public readonly Invoice $invoice,
        public readonly array $company,
        private readonly string $pdfBytes,
    ) {}

    public function envelope(): Envelope
    {
        $docType = $this->invoice->isPaid() ? 'Payment Receipt' : 'Invoice';
        $companyName = $this->company['name'] ?? config('app.name');

        return new Envelope(
            subject: "{$docType} {$this->invoice->invoice_number} — {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfBytes,
                "{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
