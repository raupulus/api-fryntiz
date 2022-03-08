<?php

namespace App\Http\Requests\Api\SmartPlant\V1;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use Illuminate\Support\Facades\Log;
use function auth;
use function log;

/**
 * Class StoreRegisterRequest
 *
 * @package App\Http\Requests\Api\SmartPlant\V1
 */
class StoreRegisterRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $smartPlantPlant = SmartPlantPlant::findOrFail($this->plant_id);

        Log::info($smartPlantPlant);

        return auth()->id() && auth()->user()->can('create',
                [SmartPlantRegister::class,
                $smartPlantPlant]);
    }

    public function prepareForValidation()
    {
        $uv = (float)($this->uv > 11 ? 11 : $this->uv);

        $this->merge([
            'user_id' => auth()->id(),
            'plant_id' => (int)$this->plant_id,
            'hardware_device_id' => (int)$this->hardware_device_id,
            'uv' => $uv,
            'pressure' => (float)$this->pressure,
            'temperature' => (float)$this->temperature,
            'humidity' => (int)$this->humidity,
            'soil_humidity' => (float)$this->soil_humidity,
            'soil_humidity_raw' => (int)$this->soil_humidity_raw,
            'full_water_tank' => (bool)$this->full_water_tank,
            'waterpump_enabled' => (bool)$this->waterpump_enabled,
            'vaporizer_enabled' => (bool)$this->vaporizer_enabled,
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
            'user_id' => 'required|exists:users,id',
            'plant_id' => 'required|numeric|exists:smartplant_plants,id',
            'hardware_device_id' => 'required|numeric|exists:hardware_devices,id',
            'uv' => 'nullable|numeric',
            'pressure' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'soil_humidity' => 'required|numeric',
            'soil_humidity_raw' => 'nullable|numeric',
            'full_water_tank' => 'nullable|boolean',
            'waterpump_enabled' => 'nullable|boolean',
            'vaporizer_enabled' => 'nullable|boolean',
        ];
    }
}
