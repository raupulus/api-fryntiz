<?php

namespace App\Http\Requests\Api\Hardware\V1;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\Hardware\HardwareDevice;
use Carbon\Carbon;
use function auth;
use function trim;

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

    protected function prepareForValidation()
    {
        $created_at = Carbon::create($this->read_at ?? $this->created_at);

        $this->merge([
            'created_at' => $this->created_at,
            'date' => $this->date ?? $created_at->format('Y-m-d'),
            'read_at' => $created_at,

            ## HardwareDevice
            'device_id' => (int)$this->device_id,
            'hardware' => $this->prepareStr($this->hardware),
            'version' => $this->prepareStr($this->version),
            'serial_number' => $this->prepareStr($this->serial_number),
            'battery_type' => $this->prepareStr($this->battery_type),
            'nominal_battery_capacity' => (int)$this->nominal_battery_capacity,

            ## HardwarePowerGenerator
            'load_current' => (float)$this->load_current,
            'load_voltage' => (float)$this->load_voltage,
            'load_power' => (int)$this->load_power,
            'battery_voltage' => (float)$this->battery_voltage,
            'battery_temperature' => (float)$this->battery_temperature,
            'battery_percentage' => (int)$this->battery_percentage,
            'charging_status' => (int)$this->charging_status,
            'charging_status_label' => $this->prepareStr($this->charging_status_label),
            'solar_current' => (float)$this->solar_current,
            'solar_voltage' => (float)$this->solar_voltage,
            'solar_power' => (int)$this->solar_power,
            'street_light_status' => (bool)$this->street_light_status,
            'street_light_brightness' => (int)$this->street_light_brightness,

            ## HardwarePowerGeneratorHistorical
            'historical_total_days_operating' => (int)$this->historical_total_days_operating,
            'historical_total_number_battery_over_discharges' => (int)$this->historical_total_number_battery_over_discharges,
            'historical_total_number_battery_full_charges' => (int)$this->historical_total_number_battery_full_charges,
            'historical_total_charging_amp_hours' => (int)$this->historical_total_charging_amp_hours,
            'historical_total_discharging_amp_hours' => (int)$this->historical_total_discharging_amp_hours,
            'historical_cumulative_power_generation' => (int)$this->historical_cumulative_power_generation,
            'historical_cumulative_power_consumption' => (int)$this->historical_cumulative_power_consumption,


            ## HardwarePowerGeneratorToday
            'battery_max_voltage'=> (float)$this->today_battery_max_voltage,
            'battery_min_voltage'=> (float)$this->today_battery_min_voltage,
            'max_charging_power'=> (int)$this->today_max_charging_power,
            'max_charging_current'=> (float)$this->today_max_charging_current,
            'max_discharging_current'=> (float)$this->today_max_discharging_current,
            'charging_amp_hours'=> (float)$this->today_charging_amp_hours,
            'discharging_amp_hours'=> (float)$this->today_discharging_amp_hours,
            'power_generation'=> (int)$this->today_power_generation,
            'power_consumption'=> (int)$this->today_power_consumption,

            ## HardwarePowerLoad
            'controller_temperature'=> (float)$this->controller_temperature,

            ## System config
            'system_voltage_current'=> (float)$this->system_voltage_current,
            'system_intensity_current'=> (float)$this->system_intensity_current,
        ]);
    }

    private function prepareStr($str)
    {
        return trim($str);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'created_at' => 'required|date',
            'date' => 'required|date',
            'read_at' => 'required|date:Y-m-d H:i:s',

            'device_id' => 'required|integer|exists:hardware_devices,id',
            'hardware' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'battery_type' => 'nullable|string|max:255',
            'nominal_battery_capacity' => 'nullable|integer',

            'load_current' => 'nullable|numeric',
            'load_voltage' => 'nullable|numeric',
            'load_power' => 'nullable|integer',
            'battery_voltage' => 'nullable|numeric',
            'battery_temperature' => 'nullable|numeric',
            'battery_percentage' => 'nullable|integer',
            'charging_status' => 'nullable|integer',
            'charging_status_label' => 'nullable|string|max:255',
            'solar_current' => 'nullable|numeric',
            'solar_voltage' => 'nullable|numeric',
            'solar_power' => 'nullable|integer',
            'street_light_status' => 'nullable|boolean',
            'street_light_brightness' => 'nullable|integer',

            'historical_total_days_operating' => 'nullable|integer',
            'historical_total_number_battery_over_discharges' => 'nullable|integer',
            'historical_total_number_battery_full_charges' => 'nullable|integer',
            'historical_total_charging_amp_hours' => 'nullable|integer',
            'historical_total_discharging_amp_hours' => 'nullable|integer',
            'historical_cumulative_power_generation' => 'nullable|integer',
            'historical_cumulative_power_consumption' => 'nullable|integer',

            'battey_max_voltage' => 'nullable|numeric',
            'battery_min_voltage' => 'nullable|numeric',
            'max_charging_power' => 'nullable|integer',
            'max_charging_current' => 'nullable|numeric',
            'max_discharging_current' => 'nullable|numeric',
            'charging_amp_hours' => 'nullable|numeric',
            'discharging_amp_hours' => 'nullable|numeric',
            'power_generation' => 'nullable|integer',
            'power_consumption' => 'nullable|integer',

            'controller_temperature' => 'nullable|numeric',
            'system_voltage_current' => 'nullable|numeric',
            'system_intensity_current' => 'nullable|numeric',
        ];
    }


}
