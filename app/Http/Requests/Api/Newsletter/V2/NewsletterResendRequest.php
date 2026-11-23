<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Newsletter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Services\CaptchaVerifierService;

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
        $captchaRequired = app(CaptchaVerifierService::class)->isConfigured();

        return [
            'email' => ['required', 'email'],
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
            'g-recaptcha-response' => [$captchaRequired ? 'required_without_all:recaptcha_token,cf-turnstile-response,turnstile_token' : 'nullable', 'string'],
            'recaptcha_token' => ['nullable', 'string'],
            'cf-turnstile-response' => ['nullable', 'string'],
            'turnstile_token' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'g-recaptcha-response.required_without_all' => 'Falta la verificación de seguridad.',
        ];
    }
}
