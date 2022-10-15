<?php

namespace App\Http\Requests\Api\KeyCounter\V1;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\KeyCounter\Keyboard;
use Carbon\Carbon;
use function auth;

/**
 * Class StoreKeyboardRequest
 */
class StoreKeyboardRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('create', Keyboard::class);
    }

    protected function prepareForValidation()
    {
        ## Calculo la duración en segundos de la racha.
        $start = new Carbon($this->start_at);
        $end = new Carbon($this->end_at);
        $duration = $start->diffInSeconds($end);

        $this->merge([
            'hardware_device_id' => (int)($this->hardware_device_id ?? $this->device_id),
            'user_id' => auth()->id(),
            'duration' => $duration,
            'pulsations' => (int)$this->pulsations,
            'pulsations_special_keys' => (int)$this->pulsations_special_keys,
            'pulsation_average' => (float)$this->pulsation_average,
            'score' => (int)$this->score,
            'weekday' => (int)$this->weekday,
        ]);

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'hardware_device_id' => 'required|integer|exists:hardware_devices,id',
            'user_id'            => 'required|integer|exists:users,id',
            'start_at' => 'required|date_format:Y-m-d H:i:s',
            'end_at' => 'required|date_format:Y-m-d H:i:s',
            'duration' => 'required|integer',
            'pulsations' => 'required|integer|min:0',
            'pulsations_special_keys' => 'required|integer|min:0',
            'pulsation_average' => 'required|numeric|min:0',
            'score' => 'required|integer|min:0',
            'weekday' => 'required|integer|min:0|max:6',
        ];
    }
}
