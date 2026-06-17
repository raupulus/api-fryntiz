<?php

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos del sensor de dirección del viento en API V2.
 */
class StoreWindDirectionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'resistance' => ['nullable', 'numeric'],
            'direction' => ['nullable', 'string', 'max:10'],
            'grades' => ['nullable', 'numeric', 'min:0', 'max:360'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'resistance.numeric' => 'La resistencia debe ser numerica.',
            'direction.string' => 'La direccion debe ser una cadena.',
            'grades.numeric' => 'Los grados deben ser numericos.',
            'grades.max' => 'Los grados no pueden superar 360.',
        ];
    }
}
