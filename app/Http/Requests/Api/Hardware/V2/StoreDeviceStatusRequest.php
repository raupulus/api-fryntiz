<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;
use App\Support\Http\ClientIp;

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

    /**
     * La IP pública la pone el servidor, no el cacharro.
     *
     * El dispositivo sabe su IP de la intranet y la manda en `ip_local`; la
     * pública no la conoce de forma fiable —tendría que preguntársela a un
     * servicio externo cada vez— y, si la manda, no hay forma de comprobar que
     * dice la verdad. Aquí ya viene en la petición: se saca de la cabecera que
     * escribe el proxy ({@see ClientIp}).
     *
     * Se sobreescribe siempre lo que mande el cliente. Si no se puede
     * determinar ninguna IP pública —desarrollo, o una NAT sin proxy delante—
     * se deja a null en vez de guardar una privada, que sería mentir en la
     * columna.
     */
    protected function passedValidation(): void
    {
        $this->merge(['ip_public' => ClientIp::public($this)]);
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
        ] + DeviceStatusPayload::rules();
    }
}
