<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Hardware\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\OwnedHardwareDevice;

/**
 * Validación para almacenar el último estado conocido de un dispositivo en la
 * API V2.
 *
 * Acepta los campos de estado directamente en el cuerpo o agrupados dentro de
 * `hardware_device_info` (para subidas conjuntas junto a otros datos). En ese
 * caso se aplanan a la raíz antes de validar.
 */
class StoreDeviceStatusRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Si el estado viene agrupado en `hardware_device_info`, lo aplana a la raíz
     * para poder validarlo con un único conjunto de reglas.
     */
    protected function prepareForValidation(): void
    {
        $info = $this->input('hardware_device_info');

        if (is_array($info)) {
            $this->merge($info);
        }
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'temp' => ['nullable', 'numeric'],
            'voltage' => ['nullable', 'numeric'],
            'battery_level' => ['nullable', 'integer', 'between:0,100'],
            'cpu' => ['nullable', 'numeric', 'between:0,100'],
            'disk' => ['nullable', 'numeric', 'between:0,100'],
            'uptime' => ['nullable', 'integer', 'min:0'],
            'ip_local' => ['nullable', 'ip'],
            'ip_public' => ['nullable', 'ip'],
            'extra' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.integer' => 'El dispositivo hardware debe ser un identificador válido.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'temp.numeric' => 'La temperatura debe ser numérica.',
            'voltage.numeric' => 'La tensión debe ser numérica.',
            'battery_level.integer' => 'El nivel de batería debe ser un número entero.',
            'battery_level.between' => 'El nivel de batería debe estar entre 0 y 100.',
            'cpu.numeric' => 'El uso de CPU debe ser numérico.',
            'cpu.between' => 'El uso de CPU debe estar entre 0 y 100.',
            'disk.numeric' => 'El uso de disco debe ser numérico.',
            'disk.between' => 'El uso de disco debe estar entre 0 y 100.',
            'uptime.integer' => 'El tiempo de actividad debe ser un número entero de segundos.',
            'uptime.min' => 'El tiempo de actividad no puede ser negativo.',
            'ip_local.ip' => 'La IP local debe ser una dirección IP válida.',
            'ip_public.ip' => 'La IP pública debe ser una dirección IP válida.',
            'extra.array' => 'El campo extra debe ser un objeto de métricas.',
        ];
    }
}
