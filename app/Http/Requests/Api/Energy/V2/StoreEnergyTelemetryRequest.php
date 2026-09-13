<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Energy\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\EnergyTelemetryPayload;
use App\Rules\OwnedHardwareDevice;

/**
 * Solicitud de ingesta de telemetría de energía universal (D115, Fase 4).
 *
 * Contrato normalizado V2:
 * ```jsonc
 * {
 *   "hardware_device_id": 1,
 *   "duration": 60,                  // Opcional: segundos del intervalo
 *   "read_at": "2026-09-13T08:15:00Z", // Opcional: cuándo se tomó la muestra
 *   "hardware_device_info": { ... }, // Opcional (salud/estado del dispositivo)
 *   "energy": {
 *     "generator": { ... }, // Opcional
 *     "battery": { ... },   // Opcional
 *     "loads": [ { ... } ]  // Opcional
 *   }
 * }
 * ```
 *
 * `duration` y `read_at` se aceptan en la raíz o dentro de `energy`, y son dos
 * cosas distintas: `duration` es **cuánto duró** el intervalo que resume la
 * muestra, y `read_at` es **cuándo** se tomó. Un aparato sin reloj manda el
 * primero; uno con reloj sincronizado puede mandar los dos.
 */
class StoreEnergyTelemetryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Normaliza alias para compatibilidad con microcontroladores y clientes IoT.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        // Alias simplificado "device" -> "hardware_device_info"
        if ($this->has('device') && ! $this->has('hardware_device_info')) {
            $merge['hardware_device_info'] = $this->input('device');
        }

        // "duration" y "read_at" valen en la raíz o dentro de "energy": el
        // firmware pone donde le viene mejor y el servidor lo entiende igual.
        $energy = is_array($this->input('energy')) ? (array) $this->input('energy') : null;

        foreach (['duration', 'read_at'] as $campo) {
            if ($energy === null) {
                continue;
            }

            if ($this->has($campo) && ! isset($energy[$campo])) {
                $energy[$campo] = $this->input($campo);
                $merge['energy'] = $energy;
            } elseif (isset($energy[$campo]) && ! $this->has($campo)) {
                $merge[$campo] = $energy[$campo];
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * Reglas de validación para la telemetría energética.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hardware_device_id' => [
                'required',
                'integer',
                'exists:hardware_devices,id',
                new OwnedHardwareDevice,
            ],
            'device' => [
                'nullable',
            ],
            'hardware_device_info' => [
                'nullable',
                new DeviceStatusPayload,
            ],
            'duration' => [
                'nullable',
                'integer',
                'min:1',
            ],
            // La hora a la que el aparato tomó la muestra. Sólo la tienen los
            // que llevan reloj; el resto no manda nada y vale la de llegada.
            'read_at' => [
                'nullable',
                'date',
                'after_or_equal:2000-01-01',
                'before_or_equal:'.now('UTC')->addHour()->toDateTimeString(),
            ],
            'energy' => [
                'required',
                'array',
                new EnergyTelemetryPayload,
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español con tildes.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El identificador del dispositivo es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo especificado no existe.',
            'duration.integer' => 'La duración del intervalo debe ser un número entero de segundos.',
            'duration.min' => 'La duración del intervalo debe ser al menos de 1 segundo.',
            'read_at.date' => 'La marca de tiempo de la lectura no es una fecha válida.',
            'read_at.after_or_equal' => 'La marca de tiempo de la lectura es anterior al año 2000; revisa el reloj del aparato.',
            'read_at.before_or_equal' => 'La marca de tiempo de la lectura está en el futuro; revisa el reloj del aparato.',
            'energy.required' => 'El bloque de datos de energía (energy) es obligatorio.',
            'energy.array' => 'El bloque de energía debe ser un objeto JSON válido.',
        ];
    }
}
