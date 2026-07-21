<?php

namespace App\Mail;

use App\Models\RegistrationApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RegistrationApplication $application,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your MP Sepaktakraw registration',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.registration.rejected',
        );
    }
}
