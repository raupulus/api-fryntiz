<?php

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos del sensor de luz en API V2.
 */
class StoreLightRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'lumens' => ['nullable', 'numeric'],
            'index' => ['nullable', 'numeric'],
            'lux' => ['nullable', 'numeric'],
            'uva' => ['nullable', 'numeric'],
            'uvb' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'lumens.numeric' => 'El valor de lumens debe ser numerico.',
            'index.numeric' => 'El indice debe ser numerico.',
            'lux.numeric' => 'El valor de lux debe ser numerico.',
            'uva.numeric' => 'El valor de UVA debe ser numerico.',
            'uvb.numeric' => 'El valor de UVB debe ser numerico.',
        ];
    }
}
