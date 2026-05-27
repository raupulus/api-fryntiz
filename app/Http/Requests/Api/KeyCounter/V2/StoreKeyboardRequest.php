<?php

namespace App\Http\Requests\Api\KeyCounter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use Carbon\Carbon;

/**
 * Validación para almacenar registro de teclado en API V2.
 * Reglas equivalentes a V1 StoreKeyboardRequest.
 */
class StoreKeyboardRequest extends BaseFormRequest
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
            'pulsations' => (int) $this->pulsations,
            'pulsations_special_keys' => (int) $this->pulsations_special_keys,
            'pulsation_average' => (float) $this->pulsation_average,
            'score' => (int) $this->score,
            'weekday' => (int) $this->weekday,
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
            'pulsations' => ['required', 'integer', 'min:0'],
            'pulsations_special_keys' => ['required', 'integer', 'min:0'],
            'pulsation_average' => ['required', 'numeric', 'min:0'],
            'score' => ['required', 'integer', 'min:0'],
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
            'pulsations.required' => 'Las pulsaciones son obligatorias.',
            'pulsations.min' => 'Las pulsaciones no pueden ser negativas.',
            'weekday.min' => 'El dia de la semana debe estar entre 0 y 6.',
            'weekday.max' => 'El dia de la semana debe estar entre 0 y 6.',
        ];
    }
}
