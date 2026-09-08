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
            // Velocidad vertical en m/s. ±50 m/s (~±9800 ft/min) ya es más de
            // lo que sostiene un avión real; por debajo de eso es una lectura
            // válida, por encima es ruido del decodificador.
            'vert_rate' => ['nullable', 'numeric', 'between:-50,50'],
            'seen' => ['nullable', 'numeric'],
            'seen_pos' => ['nullable', 'numeric'],
            'messages' => ['nullable', 'integer', 'min:0'],
            // dBFS: la columna es "siempre negativo" (ver comentario de la
            // migración). -100 es ya por debajo del suelo de ruido real de
            // cualquier receptor SDR de este tipo.
            'rssi' => ['nullable', 'numeric', 'between:-100,0'],
            // Cadena corta del decodificador ADS-B: "none", "general",
            // "lifeguard", "minfuel", "nordo", "unlawful", "downed",
            // "reserved"... Sin `in:` cerrado porque no hay garantía de que
            // el capturador solo mande exactamente esos valores.
            'emergency' => ['nullable', 'string', 'max:20'],
        ];
    }
}
