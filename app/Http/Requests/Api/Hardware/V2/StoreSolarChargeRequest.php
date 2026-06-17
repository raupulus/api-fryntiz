<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use Carbon\Carbon;

/**
 * Validación para almacenar datos de carga solar en API V2.
 * Reglas equivalentes a V1 StoreSolarChargeRequest.
 */
class StoreSolarChargeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $created_at = Carbon::create($this->read_at ?? $this->created_at ?? now()->format('Y-m-d H:i:s'));

        $this->merge([
            'created_at' => $created_at,
            'date' => $this->date ?? $created_at->format('Y-m-d'),
            'read_at' => $created_at,
            'device_id' => (int) $this->device_id,
        ]);
    }

    public function rules(): array
    {
        return [
            'created_at' => ['required', 'date'],
            'date' => ['required', 'date'],
            'read_at' => ['required', 'date'],
            'device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'hardware' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'battery_type' => ['nullable', 'string', 'max:255'],
            'battery_voltage' => ['nullable', 'numeric'],
            'battery_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'temperature' => ['nullable', 'numeric'],
            'load_voltage' => ['nullable', 'numeric'],
            'load_amperage' => ['nullable', 'numeric'],
            'load_power' => ['nullable', 'numeric'],
            'energy_voltage' => ['nullable', 'numeric'],
            'energy_amperage' => ['nullable', 'numeric'],
            'energy_power' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'El dispositivo es obligatorio.',
            'device_id.exists' => 'El dispositivo especificado no existe.',
            'created_at.required' => 'La fecha de creacion es obligatoria.',
            'date.required' => 'La fecha es obligatoria.',
            'read_at.required' => 'La fecha de lectura es obligatoria.',
            'battery_percentage.min' => 'El porcentaje de bateria no puede ser negativo.',
            'battery_percentage.max' => 'El porcentaje de bateria no puede superar el 100.',
        ];
    }
}
