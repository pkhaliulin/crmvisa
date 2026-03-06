<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecoveryLink extends Mailable
{
    use Queueable, SerializesModels;

    public string $recoveryUrl;

    public function __construct(
        public string $userName,
        public string $token,
    ) {
        $this->recoveryUrl = url("/recovery/verify?token={$token}");
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('security@visabor.uz', 'VisaBor Security'),
            subject: 'VisaBor — Восстановление доступа',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.recovery-link');
    }
}
