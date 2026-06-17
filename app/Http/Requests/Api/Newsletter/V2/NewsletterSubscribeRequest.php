<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Newsletter\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para suscripción a newsletter en API V2.
 */
class NewsletterSubscribeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El formato del correo electronico no es valido.',
            'email.max' => 'El correo electronico no puede superar los 255 caracteres.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
        ];
    }
}
