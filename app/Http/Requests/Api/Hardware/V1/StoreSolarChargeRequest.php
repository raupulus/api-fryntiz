<?php

namespace App\Http\Requests\Api\Hardware\V1;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\Hardware\HardwareDevice;
use function auth;

/**
 * Class StoreSolarChargeRequest
 */
class StoreSolarChargeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $device = HardwareDevice::findOrFail($this->device_id);

        return auth()->user()->can('storeSolarCharge', $device);
    }

    private function prepareStr($str)
    {
        return trim($str);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            ## HardwareDevice
            'device_id' => (int)$this->device_id,
            'hardware' => $this->prepareStr($this->hardware),
            'version' => $this->prepareStr($this->version),
            'serial_number' => $this->prepareStr($this->serial_number),
            'battery_type' => $this->prepareStr($this->serial_number),
            'nominal_battery_capacity' => (int)$this->nominal_battery_capacity,

            ## HardwarePowerGenerator
            'load_current' => (float) $this->load_current,
            'load_voltage' => (float) $this->load_voltage,
            'load_power' => (int) $this->load_power,
            'battery_voltage' => (float) $this->battery_voltage,
            'battery_temperature' => (float) $this->battery_temperature,
            'battery_percentage' => (int) $this->battery_percentage,
            'charging_status' => (int) $this->charging_status,
            'charging_status_label'=> $this->prepareStr($this->charging_status_label),
            'solar_current' => (float) $this->solar_current,
            'solar_voltage' => (float) $this->solar_voltage,
            'solar_power' => (int) $this->solar_power,
            'street_light_status',
            'street_light_brightness',


            ##
            /*
            'controller_temperature'=> 19,
            'system_voltage_current'=> 24,
            'system_intensity_current'=> 20,
            'today_battery_max_voltage'=> 14.6,
            'today_battery_min_voltage'=> 12.1,
            'today_max_charging_current'=> 10.12,
            'today_max_charging_power'=> 1012,
            'today_charging_amp_hours'=> 32,
            'today_discharging_amp_hours'=> 10,
            'today_power_generation'=> 435,
            'today_power_consumition'=> 120,
            'historical_total_days_operating'=> 62,
            'historical_total_number_battery_over_discharges'=> 4,
            'historical_total_number_battery_full_charges'=> 68,
            'historical_total_charging_amp_hours'=> 0,
            'historical_total_discharging_amp_hours'=> 0,
            'historical_cumulative_power_generation'=> null,
            'historical_cumulative_power_consumption'=> null
            */
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
            'device_id' => 'required|integer|exists:hardware_devices,id',
            'hardware' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'battery_type' => 'nullable|string|max:255',
            'nominal_battery_capacity' => 'nullable|integer',
        ];
    }
}
