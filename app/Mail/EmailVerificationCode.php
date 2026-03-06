<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('security@visabor.uz', 'VisaBor Security'),
            subject: "VisaBor — Код подтверждения: {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verify-email');
    }
}
