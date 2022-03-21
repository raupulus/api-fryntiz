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
            'created_at' => $created_at,
            'date' => $this->date ?? $created_at->format('Y-m-d'),
            'read_at' => $created_at,

            ## HardwareDeviceController
            'device_id' => (int)$this->device_id,
            'hardware' => $this->prepareStr($this->hardware),
            'version' => $this->prepareStr($this->version),
            'serial_number' => $this->prepareStr($this->serial_number),
            'battery_type' => $this->prepareStr($this->battery_type),
            'nominal_battery_capacity' => (int)$this->nominal_battery_capacity,

            ## Común
            'days_operating' => (int) ($this->days_operating ??  $this->historical_total_days_operating),
            'battery_voltage' => (float)$this->battery_voltage,
            'battery_max_voltage'=> (float) ($this->battery_max_voltage ?? $this->today_battery_max_voltage),
            'battery_min_voltage'=> (float) ($this->batery_min_voltage ?? $this->today_battery_min_voltage),
            'battery_percentage' => (int)$this->battery_percentage,
            'temperature' => (float) ($this->temperature ?? $this->controller_temperature),
            'battery_temperature' => (float)$this->battery_temperature,

            ## HardwarePowerLoads
            'load_fan' => (int)$this->fan,
            'load_voltage' => (float) $this->load_voltage,
            'load_amperage' => (float) ($this->load_amperage ?? $this->load_current),
            'load_power' => (float) $this->load_power,

            ## HardwarePowerGenerator
            'energy_charging_status' => (int) ($this->energy_charging_status ?? $this->charging_status),
            'energy_charging_status_label' => $this->prepareStr($this->energy_charging_status_label ?? $this->charging_status_label),
            'energy_amperage' => (float) ($this->energy_amperage ?? $this->solar_current),
            'energy_voltage' => (float) ($this->energy_voltage ?? $this->solar_voltage),
            'energy_power' => (float) ($this->energy_power ?? $this->solar_power),
            'street_light_status' => (bool)$this->street_light_status,
            'street_light_brightness' => (int)$this->street_light_brightness,

            ## HardwarePowerGeneratorHistorical
            'number_battery_over_discharges' => (int) ($this->number_battery_over_discharges ?? $this->historical_total_number_battery_over_discharges),
            'number_battery_full_charges' => (int) ($this->number_battery_full_charges ?? $this->historical_total_number_battery_full_charges),
            'historical_energy_amperage' => (float) ($this->historical_energy_amperage
                ?? $this->historical_total_charging_amp_hours),
            'historical_energy_power' => (float) ($this->historical_energy_power ?? $this->historical_cumulative_power_generation),

            ## HardwarePowerLoadsHistorical
            'historical_load_power' => (float) ($this->historical_load_power ?? $this->historical_cumulative_power_consumption),
            'historical_load_amperage' => (float) ($this->historical_load_amperage ?? $this->historical_total_discharging_amp_hours),

            ## HardwarePowerGeneratorToday
            'today_energy_power_max'=> (float) ($this->today_energy_power_max ?? $this->today_max_charging_power),
            'today_energy_amperage_max'=> (float) ($this->today_energy_amperage_max ?? $this->today_max_charging_current),
            'today_energy_power'=> (float) ($this->today_energy_power ?? $this->today_power_generation),
            'today_energy_amperage'=> (float) ($this->today_energy_amperage ?? $this->today_charging_amp_hours),

            ## HardwarePowerLoadToday
            'today_load_amperage_max'=> (float) ($this->today_load_amperage ??
                $this->today_max_discharging_current),
            'today_load_power'=> (float) ($this->today_load_power ?? $this->today_power_consumption),
            'today_load_amperage'=> (float) ($this->today_load_amperage ?? $this->today_discharging_amp_hours),

            ## System config
            'system_voltage'=> (float) ($this->system_voltage ?? $this->system_voltage_current),
            'system_intensity'=> (float) ($this->system_intensity ?? $this->system_intensity_current),
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

            ## Común
            'days_operating' => 'nullable|integer',
            'battery_voltage' => 'nullable|numeric',
            'battery_max_voltage' => 'nullable|numeric',
            'battery_min_voltage' => 'nullable|numeric',
            'battery_percentage' => 'nullable|integer',
            'battery_temperature' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',

            ## HardwarePowerLoads
            'load_fan' => 'nullable|integer',
            'load_voltage' => 'nullable|numeric',
            'load_amperage' => 'nullable|numeric',
            'load_power' => 'nullable|numeric',

            ## HardwarePowerGenerator
            'energy_charging_status' => 'nullable|integer',
            'energy_charging_status_label' => 'nullable|string|max:255',
            'energy_amperage' => 'nullable|numeric',
            'energy_voltage' => 'nullable|numeric',
            'energy_power' => 'nullable|numeric',
            'street_light_status' => 'nullable|boolean',
            'street_light_brightness' => 'nullable|integer',

            ## HardwarePowerGeneratorHistorical
            'number_battery_over_discharges' => 'nullable|integer',
            'number_battery_full_charges' => 'nullable|integer',
            'historical_energy_amperage' => 'nullable|numeric',
            'historical_energy_power' => 'nullable|numeric',

            ## HardwarePowerLoadsHistorical
            'historical_load_power' => 'nullable|numeric',
            'historical_load_amperage' => 'nullable|numeric',


            ## HardwarePowerGeneratorToday
            'today_energy_power' => 'nullable|numeric',
            'today_energy_power_max' => 'nullable|numeric',
            'today_energy_amperage' => 'nullable|numeric',
            'today_energy_amperage_max' => 'nullable|numeric',

            ## HardwarePowerLoadToday
            'today_load_power' => 'nullable|numeric',
            'today_load_amperage' => 'nullable|numeric',
            'today_load_amperage_max' => 'nullable|numeric',

            ## System config
            'system_voltage' => 'nullable|numeric',
            'system_intensity' => 'nullable|numeric',
        ];
    }
}
