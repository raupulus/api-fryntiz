<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\KeyCounter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;
use Carbon\Carbon;

/**
 * Validación para almacenar registro de teclado en API V2.
 */
class StoreKeyboardRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $merge = ['user_id' => auth()->id()];

        // `device_id` era el nombre en V1.
        $device = $this->input('hardware_device_id') ?? $this->input('device_id');
        if ($device !== null) {
            $merge['hardware_device_id'] = (int) $device;
        }

        // `duration` se calcula, pero sólo si vienen las dos fechas: `new
        // Carbon(null)` es "ahora", y sin esta guarda una petición sin fechas
        // se guardaba con duración 0 en vez de dar 422.
        if ($this->filled('start_at') && $this->filled('end_at')) {
            $merge['duration'] = (new Carbon($this->start_at))->diffInSeconds(new Carbon($this->end_at));
        }

        // Castear a ciegas convierte "no tengo dato" en 0 y **anula el
        // `required` de las reglas**: `(int) null` es 0, y 0 pasa la validación.
        // Por eso una petición sin `score` respondía 201 con un score inventado.
        foreach (['pulsations', 'pulsations_special_keys', 'score', 'weekday'] as $field) {
            if ($this->filled($field)) {
                $merge[$field] = (int) $this->input($field);
            }
        }

        if ($this->filled('pulsation_average')) {
            $merge['pulsation_average'] = (float) $this->input('pulsation_average');
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            // AR-V01: este bloque llegaba sin validar y se lo comía
            // `HardwareService::updateDeviceStatus()`. El servicio filtra las
            // claves, pero no los valores.
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'start_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'duration' => ['required', 'integer'],
            'pulsations' => ['required', 'integer', 'min:0'],
            'pulsations_special_keys' => ['required', 'integer', 'min:0'],
            'pulsation_average' => ['required', 'numeric', 'min:0'],
            'score' => ['required', 'integer', 'min:0'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
        ];
    }
}
