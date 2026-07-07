<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\OwnedHardwareDevice;

/**
 * Validación para almacenar datos de energía en API V2.
 */
class StoreEnergyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'cpu_avg' => ['nullable', 'numeric'],
            'intensity' => ['nullable', 'array'],
            'intensity.*' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'cpu_avg.numeric' => 'El promedio de CPU debe ser numerico.',
            'intensity.array' => 'La intensidad debe ser un array.',
        ];
    }
}
