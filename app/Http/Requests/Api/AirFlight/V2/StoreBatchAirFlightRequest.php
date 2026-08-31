<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\AirFlight\V2;

use App\Http\Requests\Api\BaseFormRequest;
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
            'hardware_device_id' => ['nullable', 'integer', 'exists:hardware_devices,id', new OwnedHardwareDevice],
            'data' => ['required', 'array', 'min:1', 'max:500'],
            'data.*.icao' => ['required', 'string', 'max:10'],
            'data.*.flight' => ['nullable', 'string', 'max:20'],
            'data.*.squawk' => ['nullable', 'string', 'max:10'],
            'data.*.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'data.*.lon' => ['nullable', 'numeric', 'between:-180,180'],
            'data.*.altitude' => ['nullable', 'numeric', 'min:0'],
            'data.*.speed' => ['nullable', 'numeric', 'min:0'],
            'data.*.track' => ['nullable', 'numeric', 'between:0,360'],
            'data.*.seen' => ['nullable', 'numeric'],
            'data.*.seen_pos' => ['nullable', 'numeric'],
            'data.*.messages' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
