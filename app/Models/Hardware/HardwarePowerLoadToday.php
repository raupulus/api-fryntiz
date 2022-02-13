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

    protected $fillable = ['hardware_device_id', 'fan_min', 'fan_max', 'fan_avg',
        'temperature_min', 'temperature_max', 'temperature_avg', 'voltage_min',
        'voltage_max', 'voltage_avg', 'amperage_min', 'amperage_max', 'amperage_avg',
        'date', 'created_at'];

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
        $fan = $request->get('fan');
        $temperature = $request->get('temperature') ?? $request->get('controller_temperature');
        $voltage = $request->get('voltage') ?? $request->get('controller_voltage');
        $amperage = $request->get('amperage') ?? $request->get('controller_amperage');

        $data = [
            'hardware_device_id' => $device->id,
            'date' => $request->get('date'),
            'fan_min' => $fan,
            'fan_max' => $fan,
            'fan_avg' => $fan,
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'temperature_avg' => $temperature,
            'voltage_min' => $voltage,
            'voltage_max' => $voltage,
            'voltage_avg' => $voltage,
            'amperage_min' => $amperage,
            'amperage_max' => $amperage,
            'amperage_avg' => $amperage,
            'created_at' => $request->get('created_at') ?? Carbon::now(),
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
        $fan = $request->get('fan');
        $temperature = $request->get('temperature') ?? $request->get('controller_temperature');
        $voltage = $request->get('voltage') ?? $request->get('system_voltage_current');
        $amperage = $request->get('amperage') ?? $request->get('system_intensity_current');

        $fanMin = $fan < $this->fan_min ? $fan : $this->fan_min;
        $fanMax = $fan > $this->fan_max ? $fan : $this->fan_max;
        $temperatureMin = $temperature < $this->temperature_min ? $temperature : $this->temperature_min;
        $temperatureMax = $temperature > $this->temperature_max ? $temperature : $this->temperature_max;
        $voltageMin = $voltage < $this->voltage_min ? $voltage : $this->voltage_min;
        $voltageMax = $voltage > $this->voltage_max ? $voltage : $this->voltage_max;
        $amperageMin = $amperage < $this->amperage_min ? $amperage : $this->amperage_min;
        $amperageMax = $amperage > $this->amperage_max ? $amperage : $this->amperage_max;

        $data = [
            'fan_min' => $fanMin,
            'fan_max' => $fanMax,
            'fan_avg' => ($fanMin + $fanMax) / 2,
            'temperature_min' => $temperatureMin,
            'temperature_max' => $temperatureMax,
            'temperature_avg' => ($temperatureMin + $temperatureMax) / 2,
            'voltage_min' => $voltageMin,
            'voltage_max' => $voltageMax,
            'voltage_avg' => ($voltageMin + $voltageMax) / 2,
            'amperage_min' => $amperageMin,
            'amperage_max' => $amperageMax,
            'amperage_avg' => ($amperageMin + $amperageMax) / 2,
            'created_at' => $request->get('created_at') ?? Carbon::now(),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
