<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PhoneChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $oldPhone,
        public string $newPhone,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('security@visabor.uz', 'VisaBor Security'),
            subject: 'VisaBor — Номер телефона изменён',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.phone-changed');
    }
}
