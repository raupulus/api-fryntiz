<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\AirFlight\V2;

use App\Http\Requests\Api\BaseFormRequest;
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
            // Opcional: no todos los receptores lo mandan. Si viene, se
            // comprueba que sea del usuario (**N293**).
            'hardware_device_id' => ['nullable', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'icao' => ['required', 'string', 'max:10'],
            'flight' => ['nullable', 'string', 'max:20'],
            'squawk' => ['nullable', 'string', 'max:10'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric', 'min:0'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'track' => ['nullable', 'numeric', 'between:0,360'],
            'seen' => ['nullable', 'numeric'],
            'seen_pos' => ['nullable', 'numeric'],
            'messages' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
