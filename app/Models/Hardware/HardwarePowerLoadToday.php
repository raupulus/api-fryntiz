<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use function array_filter;

/**
 * Class HardwarePowerLoadToday
 *
 * Model for the hardware power load today table
 *
 * @package App\Models\Hardware
 */
class HardwarePowerLoadToday extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_loads_today';

    protected $fillable = ['hardware_device_id', 'fan_min', 'fan_max',
        'temperature_min', 'temperature_max', 'voltage_min', 'voltage_max',
        'amperage_min', 'amperage_max', 'amperage', 'power_min', 'power_max',
        'power', 'battery_min', 'battery_max', 'battery_percentage_min',
        'battery_percentage_max', 'date', 'read_at'];

    /**
     * Prepara el modelo para ser guardado a partir de los datos de una
     * request.
     *
     * @param \App\Models\Hardware\HardwareDevice $device
     * @param                                     $request
     *
     * @return \App\Models\Hardware\HardwarePowerGeneratorHistorical
     */
    public static function createModel(HardwareDevice $device, $request)
    {
        $fan = $request->get('load_fan');
        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');
        $temperature = $request->get('temperature');
        $battery = $request->get('battery_voltage');

        $data = [
            'hardware_device_id' => $device->id,
            'date' => $request->get('date') ?? Carbon::now()->format('Y-m-d'),
            'read_at' => $request->get('read_at') ?? Carbon::now(),
            'fan_min' => $fan,
            'fan_max' => $fan,
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'voltage_min' => $voltage,
            'voltage_max' => $voltage,
            'battery_min' => $request->get('battery_min_voltage') ?? $battery,
            'battery_max' => $request->get('battery_max_voltage') ?? $battery,
            'battery_percentage_min' => $request->get('battery_percentage'),
            'battery_percentage_max' => $request->get('battery_percentage'),
            'amperage_min' => $amperage,
            'amperage_max' => $request->get('today_load_amperage_max') ?? $amperage,
            'amperage' => $request->get('today_load_amperage'),
            'power_min' => $power,
            'power_max' => $power,
            'power' => $request->get('today_load_power'),
        ];

        return new self(array_filter($data, 'strlen'));
    }

    /**
     * Prepara el modelo para ser actualizado a partir de los datos de una
     * request.
     *
     * @param $request
     *
     * @return $this
     */
    public function updateModel($request)
    {
        $fan = $request->get('load_fan');
        $fanMin = ($fan < $this->fan_min) ? $fan : $this->fan_min;
        $fanMax = ($fan > $this->fan_max) ? $fan : $this->fan_max;
        $temperature = $request->get('temperature');
        $temperatureMin = ($temperature < $this->temperature_min) ? $temperature : $this->temperature_min;
        $temperatureMax = ($temperature > $this->temperature_max) ? $temperature : $this->temperature_max;

        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');
        $battery = $request->get('battery_voltage');

        if (! $amperage && $power && $voltage) {
            $amperage = $power / $voltage;
        }

        if (! $voltage && $power && $amperage) {
            $voltage = $power / $amperage;
        }

        if (! $power && $amperage && $voltage) {
            $power = $amperage * $voltage;
        }

        $amperageTotal = $request->get('today_load_amperage') ?? $this->amperage;
        $amperageMin = ($amperage < $this->amperage_min) ? $amperage : $this->amperage_min;
        $amperageMaxCheck = $request->get('today_load_amperage_max') ?? $amperage;
        $amperageMax = ($amperageMaxCheck > $this->amperage_max) ? $amperageMaxCheck : $this->amperage_max;

        $powerTotal = $request->get('today_load_power') ?? $this->power;
        $powerMin = ($power < $this->power_min) ? $power : $this->power_min;
        $powerMax = ($power > $this->power_max) ? $power : $this->power_max;

        $voltageMin = ($voltage < $this->voltage_min) ? $voltage : $this->voltage_min;
        $voltageMax = ($voltage > $this->voltage_max) ? $voltage : $this->voltage_max;

        $batteryMinCheck = $request->get('battery_min_voltage') ?? $battery;
        $batteryMin = ($batteryMinCheck < $this->battery_min_voltage) ? $batteryMinCheck : $this->battery_min_voltage;
        $batteryMaxCheck = $request->get('battery_max_voltage') ?? $battery;
        $batteryMax = ($batteryMaxCheck > $this->battery_max_voltage) ? $batteryMaxCheck : $this->battery_max_voltage;
        $batteryPercentage = $request->get('battery_percentage');

        $data = [
            'read_at' => $request->get('read_at') ?? Carbon::now(),
            'fan_min' => $fanMin,
            'fan_max' => $fanMax,
            'temperature_min' => $temperatureMin,
            'temperature_max' => $temperatureMax,
            'voltage_min' => $voltageMin,
            'voltage_max' => $voltageMax,
            'battery_min' => $batteryMin,
            'battery_max' => $batteryMax,
            'battery_percentage_min' => ($batteryPercentage <= $this->battery_percentage_min) ? $batteryPercentage : $this->battery_percentage_min,
            'battery_percentage_max' => ($batteryPercentage >= $this->battery_percentage_max) ? $batteryPercentage : $this->battery_percentage_max,
            'amperage_min' => $amperageMin,
            'amperage_max' => $amperageMax,
            'amperage' => ($amperageTotal > $this->amperage) ? $amperageTotal : $this->amperage,
            'power_min' => $powerMin,
            'power_max' => $powerMax,
            'power' => ($powerTotal > $this->power) ? $powerTotal : $this->power,
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
