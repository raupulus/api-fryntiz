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
            // Consentimientos: la web los manda, y hay que poder demostrar que
            // se aceptaron.
            'privacity' => ['sometimes', 'boolean'],
            'contactme' => ['sometimes', 'boolean'],
            // Campos libres que quiera añadir cada web (teléfono, empresa…).
            'attributes' => ['sometimes', 'array', 'max:20'],
            'attributes.*' => ['nullable', 'string', 'max:255'],
            'g-recaptcha-response' => [! empty(config('google.recaptcha.secret_key')) ? 'required' : 'nullable', 'string'],
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
            'g-recaptcha-response.required' => 'Falta la verificación de seguridad.',
        ];
    }
}
