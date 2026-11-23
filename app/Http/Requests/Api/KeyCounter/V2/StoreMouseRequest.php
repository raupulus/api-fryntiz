<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\KeyCounter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;
use Carbon\Carbon;

/**
 * Validación para almacenar registro de ratón en API V2.
 */
class StoreMouseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $merge = ['user_id' => auth()->id()];

        $device = $this->input('hardware_device_id') ?? $this->input('device_id');
        if ($device !== null && $this->input('hardware_device_id') === null) {
            $merge['hardware_device_id'] = $device;
        }

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
            'clicks_left' => ['required', 'integer', 'min:0'],
            'clicks_right' => ['required', 'integer', 'min:0'],
            'clicks_middle' => ['required', 'integer', 'min:0'],
            'total_clicks' => ['required', 'integer', 'min:0'],
            // AD-T02: la columna es NOT NULL sin default; `nullable` dejaba
            // pasar una petición que luego revienta el INSERT con un 500.
            'clicks_average' => ['required', 'integer', 'min:0'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
        ];
    }
}
