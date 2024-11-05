<?php

namespace App\Http\Requests\Api\WeatherStation;

use App\Http\Requests\Api\BaseFormRequest;
use Carbon\Carbon;
use function auth;

/**
 * Class StoreLightningRequest
 */
class StoreLightningRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('read_at') || $this->has('created_at')) {
            $created_at = Carbon::create($this->read_at ?? $this->created_at);
        } elseif ($this->has('read_seconds_ago')) {
            $created_at = Carbon::now()->subSeconds((int) $this->read_seconds_ago);
        } else {
            $created_at = Carbon::now();
        }

        $this->merge([
            'created_at' => $created_at->format('Y-m-d H:i:s'),
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'hardware_device_id' => 'required|exists:hardware_devices,id',
            'distance' => 'required|numeric|min:0',
            'energy' => 'required|numeric|min:0',
            'noise_floor' => 'nullable|numeric|min:0',
            'created_at' => 'required|date_format:Y-m-d H:i:s',
            'user_id' => 'required|exists:users,id',
        ];
    }
}
