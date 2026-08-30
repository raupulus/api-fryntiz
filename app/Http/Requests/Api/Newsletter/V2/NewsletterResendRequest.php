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
}
