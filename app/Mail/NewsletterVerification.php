<?php

namespace App\Mail;

use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterVerification extends Mailable
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
        return $this->subject('Confirma tu suscripción a ' . config('app.name'))
            ->view('mail.newsletter')
            ->with([
                'type' => 'verification',
                'newsletter' => $this->newsletter,
                'actionUrl' => $this->newsletter->getVerificationUrl(),
                'actionText' => 'Confirmar Suscripción',
            ]);
    }
}
