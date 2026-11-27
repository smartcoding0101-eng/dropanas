<?php

namespace App\Mail;

use App\Models\WalletTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class WalletTopupRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WalletTopup $topup,
        public string $adminEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🔔 [Dropana FinTech] Nueva Solicitud de Recarga #{$this->topup->topup_number} - Bs. " . number_format((float) $this->topup->amount_bob, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet-topup-requested',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->topup->receipt_path && Storage::disk('public')->exists($this->topup->receipt_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->topup->receipt_path)
                ->as('comprobante-recarga-' . $this->topup->topup_number . '.' . pathinfo($this->topup->receipt_path, PATHINFO_EXTENSION));
        }

        return $attachments;
    }
}
