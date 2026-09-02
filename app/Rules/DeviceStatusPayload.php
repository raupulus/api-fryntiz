<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

/**
 * Valida el bloque opcional `hardware_device_info` de una subida IoT.
 *
 * Seis endpoints aceptan ese bloque y se lo pasan a
 * `HardwareService::updateDeviceStatus()`, pero **sólo uno lo validaba**
 * (`PUT /hardware/devices/{device}/status`, con su `StoreDeviceStatusRequest`).
 * Por los otros cinco entraba sin pasar por nada (auditoría AR-V01):
 *
 *   POST /hardware/energy-readings
 *   POST /hardware/solar-readings
 *   POST /weather-stations/{station}/readings
 *   POST /keycounter/{keyboard,mouse}-sessions
 *   POST /smartplant/plants/{plant}/readings
 *   POST /airflight/aircrafts[/batch]
 *
 * El servicio filtra las **claves** con lista blanca, así que asignación masiva
 * no había. Lo que no filtraba son los **valores**: sin esto, `battery_level`
 * podía llegar a 900, `ip_local` ser cualquier cosa, y `extra` —que se guarda
 * como JSON— crecer sin freno. Es el mismo problema que `StoreDeviceStatusRequest`
 * ya documentaba para su ruta («un cacharro, o quien le robe el token, puede
 * engordar la fila sin freno», fix1 #12) resuelto para todas.
 *
 * Y hay un segundo efecto: un valor con el tipo equivocado —un texto en `temp`,
 * un array en `cpu`— lo rechazaba PostgreSQL, o sea un 500 en una ruta de
 * ingesta.
 */
class DeviceStatusPayload implements ValidationRule
{
    /** Máximo de claves admitidas en `extra`. */
    public const MAX_EXTRA_KEYS = 30;

    /** Longitud máxima de cada valor de `extra`. */
    public const MAX_EXTRA_LENGTH = 255;

    /**
     * Campos de estado admitidos, con sus reglas.
     *
     * Es la misma lista que aplica `HardwareService::updateDeviceStatus()` al
     * filtrar claves. Si se añade un campo allí, va aquí también.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'temp' => ['nullable', 'numeric'],
            'voltage' => ['nullable', 'numeric'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'cpu' => ['nullable', 'numeric', 'between:0,100'],
            'disk' => ['nullable', 'numeric', 'between:0,100'],
            'uptime' => ['nullable', 'integer', 'min:0'],
            'ip_local' => ['nullable', 'ip'],
            'ip_public' => ['nullable', 'ip'],
            'extra' => ['nullable', 'array', 'max:'.self::MAX_EXTRA_KEYS],
            'extra.*' => ['nullable', new SimpleBoundedValue(self::MAX_EXTRA_LENGTH)],
        ];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            $fail('El bloque de estado del dispositivo debe ser un objeto.');

            return;
        }

        // Las claves que no son de estado se ignoran, no se rechazan: el
        // servicio ya las descarta con su lista blanca, y un firmware que mande
        // un campo de más no tiene por qué llevarse un 422.
        $conocidas = array_intersect_key($value, array_flip(array_keys(self::rules())));

        $validator = Validator::make($conocidas, self::rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $fail($error);
            }
        }
    }
}
