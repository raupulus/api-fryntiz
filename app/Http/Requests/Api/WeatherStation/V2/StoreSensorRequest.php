<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos de un sensor específico en API V2.
 * Se usa para temperature/store, humidity/store, pressure/store.
 */
class StoreSensorRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'value' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'value.required' => 'El valor del sensor es obligatorio.',
            'value.numeric' => 'El valor del sensor debe ser numerico.',
        ];
    }
}
