<?php

namespace App\Mail;

use App\Models\RegistrationApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RegistrationApplication $application,
        public string $userId,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your MP Sepaktakraw registration is approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.registration.approved',
        );
    }
}
