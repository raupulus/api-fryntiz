<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use function array_filter;

/**
 * Class HardwarePowerGeneratorToday
 */
class HardwarePowerGeneratorToday extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_generators_today';

    protected $fillable = ['hardware_device_id', 'temperature_min', 'temperature_max',
        'voltage_min', 'voltage_max', 'battery_min', 'battery_max',
        'amperage_max', 'amperage', 'power_max',
        'power', 'date', 'read_at'];

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
        $temperature = $request->get('temperature');
        $voltage = $request->get('energy_voltage');
        $batteryMin = $request->get('battery_min_voltage') ?? $request->get('battery_voltage');
        $batteryMax = $request->get('battery_max_voltage') ?? $request->get('battery_voltage');

        $data = [
            'hardware_device_id' => $device->id,
            'date' => $request->get('date'),
            'read_at' => $request->get('read_at'),
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'voltage_min' => $voltage,
            'voltage_max' => $voltage,
            'battery_min' => $batteryMin,
            'battery_max' => $batteryMax,
            'amperage' => $request->get('today_energy_amperage'),
            'amperage_max' => $request->get('today_energy_amperage_max'),
            'power' => $request->get('today_energy_power'),
            'power_max' => $request->get('today_energy_power_max'),
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
        $temperature = $request->get('temperature');
        $voltage = $request->get('energy_voltage');
        $batteryMin = $request->get('battery_min_voltage') ?? $request->get('battery_voltage');
        $batteryMax = $request->get('battery_max_voltage') ?? $request->get('battery_voltage');

        $data = [
            'date' => $request->get('date'),
            'read_at' => $request->get('read_at'),
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'voltage_min' => $voltage <= $this->voltage_min ? $voltage : $this->voltage_min,
            'voltage_max' => $voltage >= $this->voltage_max ? $voltage : $this->voltage_max,
            'battery_min' => $batteryMin <= $this->battery_min ? $batteryMin : $this->battery_min,
            'battery_max' => $batteryMax >= $this->battery_max ? $batteryMax : $this->battery_max,
            'amperage' => $request->get('today_energy_amperage'),
            'amperage_max' => $request->get('today_energy_amperage_max'),
            'power' => $request->get('today_energy_power'),
            'power_max' => $request->get('today_energy_power_max'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
