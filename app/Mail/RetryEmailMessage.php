<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Email as EmailModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable para reintento de envío de emails desde el panel admin.
 */
class RetryEmailMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmailModel $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->email->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.retry', with: ['email' => $this->email]);
    }
}
