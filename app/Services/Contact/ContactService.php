<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

/**
 * Servicio encargado de gestionar las comunicaciones y formularios de contacto.
 */
class ContactService
{
    /**
     * Envía un correo electrónico a los administradores con la información del formulario de contacto.
     *
     * @param array $data Datos validados procedentes del formulario.
     * @return void
     */
    public function sendContactForm(array $data): void
    {
        Mail::to(config('mail.admin_address', config('mail.from.address')))
            ->send(new ContactMail($data));
    }
}
