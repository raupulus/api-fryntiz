<?php

namespace App\Http\Requests\Api\KeyCounter\V1;

use App\Http\Requests\Api\BaseFormRequest;
use Carbon\Carbon;
use function auth;

/**
 * Class StoreMouseRequest
 * @package App\Http\Requests\Api\KeyCounter\V1
 */
class StoreMouseRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('create',
                \App\Models\KeyCounter\Mouse::class);
    }

    protected function prepareForValidation()
    {
        ## Calculo la duración en segundos de la racha.
        $start = new Carbon($this->start_at);
        $end = new Carbon($this->end_at);
        $duration = $start->diffInSeconds($end);

        $this->merge([
            'hardware_device_id' => (int) ($this->hardware_device_id ?? $this->device_id),
            'user_id' => auth()->id(),
            'duration' => $duration,
            'clicks_left' => (int)$this->clicks_left,
            'clicks_right' => (int)$this->clicks_right,
            'clicks_middle' => (int)$this->clicks_middle,
            'total_clicks' => (int)$this->total_clicks,
            'score' => (int)$this->score,
            'weekday' => (int)$this->weekday,
            'clicks_average' => (int)$this->clicks_average,
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
            'clicks_left' => 'required|integer|min:0',
            'clicks_right' => 'required|integer|min:0',
            'clicks_middle' => 'required|integer|min:0',
            'total_clicks' => 'required|integer|min:0',
            'clicks_average' => 'numeric|integer|min:0',
            'weekday' => 'required|integer|min:0|max:6',
        ];
    }
}
