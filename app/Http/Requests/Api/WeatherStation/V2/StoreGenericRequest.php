<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\WeatherStation\V2;

use App\Http\Requests\Api\BaseFormRequest;

/**
 * Validación para almacenar datos genéricos de estación meteorológica en API V2.
 */
class StoreGenericRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'data' => ['required', 'array', 'min:1'],
            'data.*' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'data.required' => 'Los datos son obligatorios.',
            'data.array' => 'Los datos deben ser un array.',
            'data.min' => 'Debe enviar al menos un grupo de datos.',
        ];
    }
}
