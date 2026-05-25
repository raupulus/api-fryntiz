<?php

namespace App\Http\Requests\Api\AirFlight\V2;

use App\Http\Requests\Api\BaseFormRequest;

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
            'icao'     => ['nullable', 'string', 'max:10'],
            'flight'   => ['nullable', 'string', 'max:20'],
            'squawk'   => ['nullable', 'string', 'max:10'],
            'lat'      => ['nullable', 'numeric', 'between:-90,90'],
            'lon'      => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric', 'min:0'],
            'speed'    => ['nullable', 'numeric', 'min:0'],
            'track'    => ['nullable', 'numeric', 'between:0,360'],
            'seen'     => ['nullable', 'numeric'],
            'seen_pos' => ['nullable', 'numeric'],
            'messages' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.between' => 'La latitud debe estar entre -90 y 90.',
            'lon.between' => 'La longitud debe estar entre -180 y 180.',
            'altitude.min' => 'La altitud no puede ser negativa.',
            'speed.min' => 'La velocidad no puede ser negativa.',
            'track.between' => 'El rumbo debe estar entre 0 y 360 grados.',
        ];
    }
}
