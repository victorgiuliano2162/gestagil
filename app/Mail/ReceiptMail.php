<?php

namespace App\Mail;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Receipt $receipt,
        public string $pdfBinary
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->receipt->issuer_snapshot['store_name'] ?? 'GestÁgil';

        return new Envelope(
            subject: "Comprovante de Pagamento - Recibo Nº {$this->receipt->formatted_number} ({$storeName})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt',
        );
    }

    public function attachments(): array
    {
        $filename = 'recibo-' . str_replace('/', '-', $this->receipt->formatted_number) . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
