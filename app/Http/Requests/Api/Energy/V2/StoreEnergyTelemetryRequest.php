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
 *   "hardware_device_info": { ... }, // Opcional (salud/estado del dispositivo)
 *   "energy": {
 *     "duration": 60, // Segundos del intervalo
 *     "generator": { ... }, // Opcional
 *     "battery": { ... },   // Opcional
 *     "loads": [ { ... } ]  // Opcional
 *   }
 * }
 * ```
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

        // Si "duration" viene en la raíz y "energy" es array sin duration, propagarlo
        if ($this->has('duration') && $this->has('energy') && is_array($this->input('energy'))) {
            $energy = (array) $this->input('energy');
            if (! isset($energy['duration'])) {
                $energy['duration'] = $this->input('duration');
                $merge['energy'] = $energy;
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
            'energy.required' => 'El bloque de datos de energía (energy) es obligatorio.',
            'energy.array' => 'El bloque de energía debe ser un objeto JSON válido.',
        ];
    }
}
