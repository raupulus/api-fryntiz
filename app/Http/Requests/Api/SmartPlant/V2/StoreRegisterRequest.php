<?php

namespace App\Http\Requests\Api\SmartPlant\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar registro de planta en API V2.
 * Reglas equivalentes a V1 StoreRegisterRequest.
 */
class StoreRegisterRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => auth()->id(),
            'plant_id' => (int)$this->plant_id,
            'hardware_device_id' => (int)$this->hardware_device_id,
            'soil_humidity' => (float)$this->soil_humidity,
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id'            => ['required', 'exists:users,id'],
            'plant_id'           => ['required', 'numeric', 'exists:smartplant_plants,id'],
            'hardware_device_id' => ['required', 'numeric', 'exists:hardware_devices,id'],
            'uv'                 => ['nullable', 'numeric'],
            'pressure'           => ['nullable', 'numeric'],
            'temperature'        => ['nullable', 'numeric'],
            'humidity'           => ['nullable', 'numeric'],
            'soil_humidity'      => ['required', 'numeric'],
            'soil_humidity_raw'  => ['nullable', 'numeric'],
            'full_water_tank'    => ['nullable', 'boolean'],
            'waterpump_enabled'  => ['nullable', 'boolean'],
            'vaporizer_enabled'  => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'plant_id.required' => 'La planta es obligatoria.',
            'plant_id.exists' => 'La planta especificada no existe.',
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'soil_humidity.required' => 'La humedad del suelo es obligatoria.',
            'soil_humidity.numeric' => 'La humedad del suelo debe ser un valor numerico.',
        ];
    }
}
