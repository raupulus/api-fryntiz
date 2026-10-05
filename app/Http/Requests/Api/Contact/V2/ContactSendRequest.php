<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Contact\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Services\CaptchaVerifierService;

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
        // Con algún proveedor configurado hace falta un token, de cualquiera de
        // los dos. El error cuelga de `g-recaptcha-response` por
        // retrocompatibilidad: es la clave que los clientes ya esperan.
        $captchaRequired = app(CaptchaVerifierService::class)->isConfigured();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Consentimientos: la web los manda, y hay que poder demostrar que
            // se aceptaron.
            'privacity' => ['sometimes', 'boolean'],
            'contactme' => ['sometimes', 'boolean'],
            // Campos libres que quiera añadir cada web (teléfono, empresa…).
            'attributes' => ['sometimes', 'array', 'max:20'],
            'attributes.*' => ['nullable', 'string', 'max:255'],
            // Token de Google reCAPTCHA v3.
            'g-recaptcha-response' => [$captchaRequired ? 'required_without_all:recaptcha_token,cf-turnstile-response,turnstile_token' : 'nullable', 'string'],
            'recaptcha_token' => ['nullable', 'string'],
            // Token de Cloudflare Turnstile.
            'cf-turnstile-response' => ['nullable', 'string'],
            'turnstile_token' => ['nullable', 'string'],
        ];
    }

    /**
     * Sólo lo que el mensaje por defecto no puede decir.
     *
     * El resto salía de aquí escrito a mano —98 cadenas repartidas por 19
     * ficheros, la mitad sin tildes y todas sólo en español— para acabar
     * diciendo lo mismo que ya dice `lang/{es,en}/validation.php`. Los nombres
     * de campo viven ahora en su bloque `attributes`, así que «El campo
     * hardware_device_id es obligatorio» sale ya como «El campo dispositivo es
     * obligatorio», en los dos idiomas y para todas las reglas.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'g-recaptcha-response.required_without_all' => 'Falta la verificación de seguridad.',
        ];
    }
}
