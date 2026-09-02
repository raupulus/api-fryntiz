<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\AirFlight\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Rules\DeviceStatusPayload;
use App\Rules\OwnedHardwareDevice;

/**
 * Validación para registrar un avión en API V2.
 */
class StoreAirFlightRequest extends BaseFormRequest
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
            // Opcional: no todos los receptores lo mandan. Si viene, se
            // comprueba que sea del usuario (**N293**).
            'hardware_device_id' => ['nullable', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'icao' => ['required', 'string', 'max:10'],
            'flight' => ['nullable', 'string', 'max:20'],
            'squawk' => ['nullable', 'string', 'max:10'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
            // AD-T01: `altitude`/`speed` acotados por arriba con un margen amplio
            // sobre lo que reporta ADS-B real, para descartar decodificaciones
            // corruptas del receptor (auditoría de datos 2026-09-02: un único
            // receptor de pruebas coló 1347 kn y -1000 ft de altitud).
            'altitude' => ['nullable', 'numeric', 'min:0', 'max:60000'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            // AD-T01: `track` es `integer` en BD; `numeric` admitía decimales
            // que revientan el INSERT con un 500.
            'track' => ['nullable', 'integer', 'between:0,360'],
            'seen' => ['nullable', 'numeric'],
            'seen_pos' => ['nullable', 'numeric'],
            'messages' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
