<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\AirFlight\V2;

use App\Http\Requests\Api\BaseFormRequest;

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
            'data' => ['required', 'array', 'min:1', 'max:500'],
            'data.*.icao' => ['nullable', 'string', 'max:10'],
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

    public function messages(): array
    {
        return [
            'data.required' => 'El campo data es obligatorio.',
            'data.array' => 'El campo data debe ser un array.',
            'data.min' => 'El lote debe contener al menos un registro.',
            'data.max' => 'El lote no puede contener mas de 500 registros.',
        ];
    }
}
