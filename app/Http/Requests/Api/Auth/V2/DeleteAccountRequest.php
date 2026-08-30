<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth\V2;

use App\Http\Requests\Api\BaseFormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * Baja de cuenta: exige reintroducir la contraseña actual.
 *
 * La ruta que lo usa está desactivada (ver `RegisterController`), pero la
 * comprobación se deja escrita para que reactivarla no vuelva a abrir A1.
 */
class DeleteAccountRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();

            if (! $user || ! Hash::check((string) $this->input('password'), (string) $user->password)) {
                $validator->errors()->add('password', 'La contraseña no es correcta.');
            }
        });
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
            'password.required' => 'Debes confirmar tu contraseña para eliminar la cuenta.',
        ];
    }
}
