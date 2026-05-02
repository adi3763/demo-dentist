<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSubmissionAcknowledgementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactSubmission $submission,
        public string $serviceName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your message'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.acknowledgement'
        );
    }
}
