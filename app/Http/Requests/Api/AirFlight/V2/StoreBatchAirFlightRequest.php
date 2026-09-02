<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\AirFlight\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;

/**
 * Validación para registro de lote de aviones en API V2.
 */
class StoreBatchAirFlightRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // AR-V01: este bloque llegaba sin validar y se lo comía
            // `HardwareService::updateDeviceStatus()`. El servicio filtra las
            // claves, pero no los valores.
            'hardware_device_info' => ['nullable', new DeviceStatusPayload],
            'hardware_device_id' => ['nullable', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'data' => ['required', 'array', 'min:1', 'max:500'],
            'data.*.icao' => ['required', 'string', 'max:10'],
            'data.*.flight' => ['nullable', 'string', 'max:20'],
            'data.*.squawk' => ['nullable', 'string', 'max:10'],
            'data.*.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'data.*.lon' => ['nullable', 'numeric', 'between:-180,180'],
            // AD-T01: mismos límites que StoreAirFlightRequest, ver ese fichero.
            'data.*.altitude' => ['nullable', 'numeric', 'min:0', 'max:60000'],
            'data.*.speed' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'data.*.track' => ['nullable', 'integer', 'between:0,360'],
            'data.*.seen' => ['nullable', 'numeric'],
            'data.*.seen_pos' => ['nullable', 'numeric'],
            'data.*.messages' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
