<?php

namespace App\Http\Requests\Api\KeyCounter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use Carbon\Carbon;

/**
 * Validación para almacenar registro de ratón en API V2.
 * Reglas equivalentes a V1 StoreMouseRequest.
 */
class StoreMouseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $start = new Carbon($this->start_at);
        $end = new Carbon($this->end_at);
        $duration = $start->diffInSeconds($end);

        $this->merge([
            'hardware_device_id' => (int) ($this->hardware_device_id ?? $this->device_id),
            'user_id' => auth()->id(),
            'duration' => $duration,
            'clicks_left' => (int) $this->clicks_left,
            'clicks_right' => (int) $this->clicks_right,
            'clicks_middle' => (int) $this->clicks_middle,
            'total_clicks' => (int) $this->total_clicks,
            'score' => (int) $this->score,
            'weekday' => (int) $this->weekday,
            'clicks_average' => (int) $this->clicks_average,
        ]);
    }

    public function rules(): array
    {
        return [
            'hardware_device_id' => ['required', 'integer', 'exists:hardware_devices,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'start_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'duration' => ['required', 'integer'],
            'clicks_left' => ['required', 'integer', 'min:0'],
            'clicks_right' => ['required', 'integer', 'min:0'],
            'clicks_middle' => ['required', 'integer', 'min:0'],
            'total_clicks' => ['required', 'integer', 'min:0'],
            'clicks_average' => ['nullable', 'integer', 'min:0'],
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'hardware_device_id.required' => 'El dispositivo hardware es obligatorio.',
            'hardware_device_id.exists' => 'El dispositivo hardware especificado no existe.',
            'start_at.required' => 'La fecha de inicio es obligatoria.',
            'start_at.date_format' => 'La fecha de inicio debe tener el formato Y-m-d H:i:s.',
            'end_at.required' => 'La fecha de fin es obligatoria.',
            'end_at.date_format' => 'La fecha de fin debe tener el formato Y-m-d H:i:s.',
            'clicks_left.required' => 'Los clicks izquierdos son obligatorios.',
            'clicks_right.required' => 'Los clicks derechos son obligatorios.',
            'clicks_middle.required' => 'Los clicks centrales son obligatorios.',
            'total_clicks.required' => 'El total de clicks es obligatorio.',
            'weekday.min' => 'El dia de la semana debe estar entre 0 y 6.',
            'weekday.max' => 'El dia de la semana debe estar entre 0 y 6.',
        ];
    }
}
