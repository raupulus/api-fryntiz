<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $newsletter
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifica tu suscripcion al newsletter',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter.verification',
            with: ['newsletter' => $this->newsletter],
        );
    }
}
