<?php

namespace App\Mail;

use App\Models\WalletWithdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class WalletWithdrawalRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public WalletWithdrawal $withdrawal,
        public string $adminEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "💸 [Dropana Retiro] Solicitud de Retiro #{$this->withdrawal->withdrawal_number} - Bs. " . number_format((float) $this->withdrawal->amount_bob, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet-withdrawal-requested',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->withdrawal->qr_code_path && Storage::disk('public')->exists($this->withdrawal->qr_code_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->withdrawal->qr_code_path)
                ->as('qr-cobro-' . $this->withdrawal->withdrawal_number . '.' . pathinfo($this->withdrawal->qr_code_path, PATHINFO_EXTENSION));
        }

        return $attachments;
    }
}
