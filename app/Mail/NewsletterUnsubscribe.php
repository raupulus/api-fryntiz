<?php

namespace App\Mail;

use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterUnsubscribe extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;

    /**
     * Create a new message instance.
     *
     * @param Newsletter $newsletter
     */
    public function __construct(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Confirmación de desuscripción - ' . config('app.name'))
            ->view('mail.newsletter')
            ->with([
                'type' => 'unsubscribe',
                'newsletter' => $this->newsletter,
                'actionUrl' => $this->newsletter->getUnsubscribeUrl(),
                'actionText' => 'Desuscribirse',
            ]);
    }
}
