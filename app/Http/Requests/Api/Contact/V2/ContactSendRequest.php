<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Contact\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para envío de formulario de contacto en API V2.
 */
class ContactSendRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El formato del correo electronico no es valido.',
            'subject.required' => 'El asunto es obligatorio.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.min' => 'El mensaje debe tener al menos :min caracteres.',
            'message.max' => 'El mensaje no puede superar los :max caracteres.',
            'g-recaptcha-response.required' => 'La verificacion de seguridad es obligatoria.',
        ];
    }
}
