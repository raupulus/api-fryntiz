<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function sendContactForm(array $data): void
    {
        Mail::to(config('mail.admin_address', config('mail.from.address')))
            ->send(new ContactMail($data));
    }
}
