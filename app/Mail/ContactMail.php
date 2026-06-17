<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $data
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo mensaje de contacto - '.($this->data['subject'] ?? 'Sin asunto'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            with: ['contactData' => $this->data],
        );
    }
}
