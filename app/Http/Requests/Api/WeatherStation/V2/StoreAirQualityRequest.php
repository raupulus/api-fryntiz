<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\OwnedHardwareDevice;

/**
 * Validación para almacenar datos del sensor de calidad del aire en API V2.
 */
class StoreAirQualityRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'gas_resistance' => ['nullable', 'numeric'],
            'air_quality' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'gas_resistance.numeric' => 'La resistencia de gas debe ser numerica.',
        ];
    }
}
