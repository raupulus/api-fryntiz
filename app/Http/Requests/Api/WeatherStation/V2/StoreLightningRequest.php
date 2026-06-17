<?php

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos del sensor de rayos/relámpagos en API V2.
 */
class StoreLightningRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'distance' => ['nullable', 'numeric', 'min:0'],
            'energy' => ['nullable', 'numeric'],
            'noise_floor' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'distance.numeric' => 'La distancia debe ser numerica.',
            'energy.numeric' => 'La energia debe ser numerica.',
            'noise_floor.numeric' => 'El nivel de ruido debe ser numerico.',
        ];
    }
}
