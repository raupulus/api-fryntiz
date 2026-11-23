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
        if ($device !== null && $this->input('hardware_device_id') === null) {
            $merge['hardware_device_id'] = $device;
        }

        // `duration` se calcula solo cuando ambas fechas cumplen el formato
        // exacto Y-m-d H:i:s; si llegan mal formadas, las reglas devuelven 422
        // en lugar de lanzar Carbon InvalidFormatException (500).
        if (
            is_string($this->input('start_at'))
            && is_string($this->input('end_at'))
            && Carbon::hasFormat($this->input('start_at'), 'Y-m-d H:i:s')
            && Carbon::hasFormat($this->input('end_at'), 'Y-m-d H:i:s')
        ) {
            $start = Carbon::createFromFormat('Y-m-d H:i:s', $this->input('start_at'));
            $end = Carbon::createFromFormat('Y-m-d H:i:s', $this->input('end_at'));
            if ($start !== null && $end !== null) {
                $merge['duration'] = (int) $start->diffInSeconds($end, false);
            }
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
            'end_at' => ['required', 'date_format:Y-m-d H:i:s', 'after_or_equal:start_at'],
            'duration' => ['required', 'integer', 'min:0'],
            'pulsations' => ['required', 'integer', 'min:0'],
            'pulsations_special_keys' => ['required', 'integer', 'min:0'],
            'pulsation_average' => ['required', 'numeric', 'min:0'],
            'score' => ['required', 'integer', 'min:0'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
        ];
    }
}
