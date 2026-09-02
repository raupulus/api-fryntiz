<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;

/**
 * El contrato de subida del monitor de energía (D115).
 *
 * ```jsonc
 * {
 *   "hardware_device_id": 7,       // quién mide
 *   "duration": 300,               // segundos que cubre la medición
 *   "readings": [
 *     {
 *       "pos": 0,                  // canal -> hardware_energy.sensor_position
 *       "amperage": 1.42,          // corriente MEDIA del periodo (A)
 *       "voltage": 12.4,           // tensión del periodo (V)
 *       "energy_wh": null          // opcional: sólo si el aparato lo da mejor
 *     }
 *   ],
 *   "battery_voltage": 3.92,       // opcional, del PROPIO dispositivo
 *   "battery_percentage": 78       // opcional
 * }
 * ```
 *
 * Tres cosas que conviene tener claras:
 *
 *  - **`amperage` es la corriente MEDIA del periodo**, no una instantánea. Si
 *    falla internet, el aparato sigue promediando y `duration` crece: una media
 *    de 20 minutos y una de 5 son igual de válidas, porque `A · s` da lo mismo.
 *  - **`duration` es lo que convierte una corriente en energía.** Sin él se
 *    guarda la potencia y se avisa en la respuesta, pero no hay vatios-hora.
 *  - **`battery_voltage` y `battery_percentage` de primer nivel son del propio
 *    dispositivo** (D108) y van a `hardware_devices`, no a las tablas de
 *    energía. Los de dentro de un `reading` son del elemento medido.
 *
 * No hay traducción de nombres antiguos: éste es el vocabulario, y el firmware
 * se ajusta a él.
 */
class StoreEnergyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // AR-V01: este bloque llegaba sin validar y se lo comía
            // `HardwareService::updateDeviceStatus()`. El servicio filtra las
            // claves, pero no los valores.
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],

            // Segundos que ha durado la medición. Sin esto no hay energía, sólo
            // potencia. Se admite por lectura para los montajes con canales de
            // cadencia distinta.
            'duration' => ['nullable', 'integer', 'min:1'],

            // Cuándo se midió. Si no viene, es ahora.
            'read_at' => ['nullable', 'date'],

            // Temperatura del propio monitor.
            'temperature' => ['nullable', 'numeric'],

            // Batería del propio dispositivo (D108). Siempre opcional.
            'battery_voltage' => ['nullable', 'numeric', 'min:0'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],

            'readings' => ['required', 'array', 'min:1'],
            'readings.*' => ['required', 'array'],

            // `pos` es obligatorio: sin él la lectura no se puede asignar a
            // ningún elemento, y se descartaría en silencio.
            'readings.*.pos' => ['required', 'integer', 'min:0'],

            // Los tres crudos. Ninguno es obligatorio, pero sin corriente no hay
            // nada que calcular y se avisa en la respuesta.
            'readings.*.amperage' => ['nullable', 'numeric'],
            'readings.*.voltage' => ['nullable', 'numeric'],
            'readings.*.duration' => ['nullable', 'integer', 'min:1'],

            // Si el aparato calcula la energía mejor que nosotros, gana la suya
            // y se marca de dónde salió.
            'readings.*.energy_wh' => ['nullable', 'numeric'],

            'readings.*.temperature' => ['nullable', 'numeric'],
            'readings.*.fan' => ['nullable', 'integer', 'min:0'],
            'readings.*.read_at' => ['nullable', 'date'],

            // Batería del elemento medido, no la del monitor.
            'readings.*.battery_voltage' => ['nullable', 'numeric', 'min:0'],
            'readings.*.battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
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
            'readings.required' => 'No hay lecturas: se espera un array «readings».',
            'readings.min' => 'No hay lecturas: se espera un array «readings».',
            'readings.*.pos.required' => 'Cada lectura necesita su canal (pos), que es lo que la asigna a un elemento.',
        ];
    }
}
