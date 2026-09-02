<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;

/**
 * Validación para almacenar el último estado conocido de un dispositivo en la
 * API V2.
 *
 * Acepta los campos de estado directamente en el cuerpo o agrupados dentro de
 * `hardware_device_info` (para subidas conjuntas junto a otros datos). En ese
 * caso se aplanan a la raíz antes de validar.
 */
class StoreDeviceStatusRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Campos que se aceptan dentro de `hardware_device_info`.
     *
     * Es una lista blanca a propósito: `merge($info)` a secas dejaba que el
     * cliente sobreescribiera CUALQUIER clave de la raíz metiéndola dentro del
     * grupo, incluido `hardware_device_id` (fix1 #13). Aquí sólo pasan los
     * campos de estado, y `hardware_device_id` nunca es uno de ellos.
     *
     * La lista sale de {@see DeviceStatusPayload::rules()}, que es la misma que
     * usan los otros ocho endpoints que aceptan el bloque. Antes vivía sólo
     * aquí, y por eso sólo protegía a esta ruta (AR-V01).
     *
     * @return array<int, string>
     */
    private static function statusFields(): array
    {
        return array_values(array_filter(
            array_keys(DeviceStatusPayload::rules()),
            static fn (string $campo): bool => ! str_contains($campo, '.')
        ));
    }

    /**
     * Si el estado viene agrupado en `hardware_device_info`, lo aplana a la raíz
     * para poder validarlo con un único conjunto de reglas.
     */
    protected function prepareForValidation(): void
    {
        // El dispositivo viene en la URL (`PUT /hardware/devices/7/status`), no
        // en el cuerpo. Se inyecta aquí para que las reglas de pertenencia
        // sigan aplicándose sobre `hardware_device_id` igual que antes.
        if ($this->route('device') !== null) {
            $this->merge(['hardware_device_id' => $this->route('device')]);
        }

        $info = $this->input('hardware_device_info');

        if (! is_array($info)) {
            return;
        }

        $allowed = array_intersect_key($info, array_flip(self::statusFields()));

        // Lo que ya viene en la raíz manda sobre lo agrupado.
        $allowed = array_diff_key($allowed, $this->except('hardware_device_info'));

        if ($allowed !== []) {
            $this->merge($allowed);
        }
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
        ] + DeviceStatusPayload::rules();
    }
}
