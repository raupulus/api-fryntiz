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

    protected $fillable = ['hardware_device_id', 'battery_min_voltage',
        'battery_max_voltage', 'max_charging_power', 'max_charging_current',
        'max_discharging_current', 'charging_amp_hours', 'discharging_amp_hours',
        'power_generation', 'power_consumption', 'date'];

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
        $data = [
            'hardware_device_id' => $device->id,
            'date' => $request->get('date'),
            'battery_min_voltage' => $request->get('battery_min_voltage') ?? $request->get('today_battery_min_voltage'),
            'battery_max_voltage' => $request->get('battery_max_voltage') ?? $request->get('today_battery_max_voltage'),
            'max_charging_power' => $request->get('max_charging_power') ?? $request->get('today_max_charging_power'),
            'max_charging_current' => $request->get('max_charging_current') ?? $request->get('today_max_charging_current'),
            'max_discharging_current' => $request->get('max_discharging_current') ?? $request->get('today_max_discharging_current'),
            'charging_amp_hours' => $request->get('charging_amp_hours') ?? $request->get('today_charging_amp_hours'),
            'discharging_amp_hours' => $request->get('discharging_amp_hours') ?? $request->get('today_discharging_amp_hours'),
            'power_generation' => $request->get('power_generation') ?? $request->get('today_power_generation'),
            'power_consumption' => $request->get('power_consumption') ?? $request->get('today_power_consumption'),
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
        $data = [
            'battery_min_voltage' => $request->get('battery_min_voltage') ?? $request->get('today_battery_min_voltage'),
            'battery_max_voltage' => $request->get('battery_max_voltage') ?? $request->get('today_battery_max_voltage'),
            'max_charging_power' => $request->get('max_charging_power') ?? $request->get('today_max_charging_power'),
            'max_charging_current' => $request->get('max_charging_current') ?? $request->get('today_max_charging_current'),
            'max_discharging_current' => $request->get('max_discharging_current') ?? $request->get('today_max_discharging_current'),
            'charging_amp_hours' => $request->get('charging_amp_hours') ?? $request->get('today_charging_amp_hours'),
            'discharging_amp_hours' => $request->get('discharging_amp_hours') ?? $request->get('today_discharging_amp_hours'),
            'power_generation' => $request->get('power_generation') ?? $request->get('today_power_generation'),
            'power_consumption' => $request->get('power_consumption') ?? $request->get('today_power_consumption'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
