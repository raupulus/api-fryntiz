<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Support\Auth\TokenAbilities;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

/**
 * Emisión de un token para un dispositivo IoT.
 *
 * Las abilities se validan contra el catálogo (`TokenAbilities`), así que ni el
 * comodín `*` ni la ability de sesión pueden colarse por aquí.
 */
class IssueDeviceTokenRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(array_keys(TokenAbilities::MODULE_ABILITIES))],
            'name' => ['nullable', 'string', 'max:255'],
            // Los tokens de dispositivo se emiten sin caducidad a propósito
            // (D1): están en sitios a los que no se sube a reflashear. Se
            // permite ponerla para casos concretos, nunca por defecto.
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Caducidad pedida, o null (que es lo normal).
     */
    public function expiresAt(): ?Carbon
    {
        $value = $this->validated('expires_at');

        return $value === null ? null : Carbon::parse($value);
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
            'device_id.required' => 'Hay que indicar a qué dispositivo se liga el token.',
            'abilities.required' => 'Hay que indicar al menos un permiso de módulo.',
            'abilities.*.in' => 'Permiso no válido. No existe un permiso comodín.',
            'expires_at.after' => 'La fecha de caducidad tiene que estar en el futuro.',
        ];
    }
}
