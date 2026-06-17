<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos del sensor de viento en API V2.
 */
class StoreWindRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'average' => ['nullable', 'numeric', 'min:0'],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'speed.numeric' => 'La velocidad del viento debe ser numerica.',
            'average.numeric' => 'La media del viento debe ser numerica.',
            'min.numeric' => 'El minimo del viento debe ser numerico.',
            'max.numeric' => 'El maximo del viento debe ser numerico.',
        ];
    }
}
