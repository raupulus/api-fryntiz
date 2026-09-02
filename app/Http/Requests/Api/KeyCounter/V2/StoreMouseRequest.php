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
        if ($device !== null) {
            $merge['hardware_device_id'] = (int) $device;
        }

        if ($this->filled('start_at') && $this->filled('end_at')) {
            $merge['duration'] = (new Carbon($this->start_at))->diffInSeconds(new Carbon($this->end_at));
        }

        // Sin `score`: `keycounter_mouse` no tiene esa columna. Se merjaba,
        // no se validaba, no se guardaba, y el Resource la devolvía a NULL
        // en todas las respuestas (**R-6**).
        foreach (['clicks_left', 'clicks_right', 'clicks_middle', 'total_clicks', 'clicks_average', 'weekday'] as $field) {
            if ($this->filled($field)) {
                $merge[$field] = (int) $this->input($field);
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
            'end_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'duration' => ['required', 'integer'],
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
