<?php

namespace App\Http\Requests\Api\WeatherStation;

use App\Http\Requests\Api\BaseFormRequest;
use Carbon\Carbon;
use function auth;

/**
 * Class StoreLightningBatchRequest
 */
class StoreLightningBatchRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // TODO: hardware_device_id tiene que corresponder al user?
        return true;
    }

    protected function prepareForValidation(): void
    {
        $lightnings = $this->input('lightnings', []);

        foreach ($lightnings as $key => &$lightning) {
            if (isset($lightning['read_at'])) {
                $created_at = Carbon::create($lightning['read_at']);
            } elseif (isset($lightning['created_at'])) {
                $created_at = Carbon::create($lightning['created_at']);
            } elseif (isset($lightning['read_seconds_ago'])) {
                $created_at = Carbon::now()->subSeconds((int) $lightning['read_seconds_ago']);
            } else {
                $created_at = Carbon::now();
            }

            $lightning['user_id'] = auth()->id();
            $lightning['hardware_device_id'] = $this->input('hardware_device_id');
            $lightning['created_at'] = $created_at->format('Y-m-d H:i:s');

            $lightnings[$key] = $lightning;
        }

        $this->merge([
            'lightnings' => $lightnings,
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
            'lightnings' => 'required|array',
            'lightnings.*.distance' => 'required|numeric|min:0',
            'lightnings.*.energy' => 'required|numeric|min:0',
            'lightnings.*.noise_floor' => 'nullable|numeric|min:0',
            'lightnings.*.created_at' => 'required|date_format:Y-m-d H:i:s',
            'lightnings.*.user_id' => 'required|exists:users,id',
        ];
    }
}
