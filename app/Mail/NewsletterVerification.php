<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NewsletterVerification extends Mailable implements ShouldQueue
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

    /**
     * Baja de un clic (RFC 8058).
     *
     * `List-Unsubscribe-Post` es lo que le dice al cliente de correo que use
     * POST. Sin esa cabecera, Gmail y Outlook harían GET sobre la URL de
     * `List-Unsubscribe`, que es justo el prefetch que daba de baja a gente que
     * no lo había pedido.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->newsletter->getOneClickUnsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }
}
