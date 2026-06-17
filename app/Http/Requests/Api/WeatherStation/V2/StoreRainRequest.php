<?php

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos del sensor de lluvia en API V2.
 */
class StoreRainRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'rain' => ['nullable', 'numeric', 'min:0'],
            'rain_intensity' => ['nullable', 'numeric', 'min:0'],
            'rain_month' => ['nullable', 'numeric', 'min:0'],
            'moisture' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'rain.numeric' => 'El valor de lluvia debe ser numerico.',
            'rain_intensity.numeric' => 'La intensidad de lluvia debe ser numerica.',
            'rain_month.numeric' => 'La lluvia mensual debe ser numerica.',
            'moisture.numeric' => 'La humedad debe ser numerica.',
        ];
    }
}
