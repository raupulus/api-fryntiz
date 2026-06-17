<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Newsletter\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para reenviar el email de verificación de newsletter en API V2.
 */
class NewsletterResendRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El formato del correo electronico no es valido.',
            'platform_id.required' => 'La plataforma es obligatoria.',
            'platform_id.exists' => 'La plataforma especificada no existe.',
        ];
    }
}
