<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use function array_filter;

/**
 * Class HardwarePowerGeneratorHistorical
 */
class HardwarePowerGeneratorHistorical extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_generators_historical';

    protected $fillable = ['hardware_device_id', 'total_days_operating',
        'total_number_battery_over_discharges', 'total_number_battery_full_charges',
        'total_charging_amp_hours', 'total_discharging_amp_hours',
        'cumulative_power_generation', 'cumulative_power_consumption', 'created_at'];


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
            'total_days_operating' => $request->get('total_days_operating') ?? $request->get('historical_total_days_operating'),
            'total_number_battery_over_discharges' => $request->get('total_number_battery_over_discharges') ?? $request->get('historical_total_number_battery_over_discharges'),
            'total_number_battery_full_charges' => $request->get('total_number_battery_full_charges') ?? $request->get('historical_total_number_battery_full_charges'),
            'total_charging_amp_hours' => $request->get('total_charging_amp_hours') ?? $request->get('historical_total_charging_amp_hours'),
            'total_discharging_amp_hours' => $request->get('total_discharging_amp_hours') ?? $request->get('historical_total_discharging_amp_hours'),
            'cumulative_power_generation' => $request->get('cumulative_power_generation') ?? $request->get('historical_cumulative_power_generation'),
            'cumulative_power_consumption' => $request->get('cumulative_power_consumption') ?? $request->get('historical_cumulative_power_consumption'),
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
            'total_days_operating' => $request->get('total_days_operating') ?? $request->get('historical_total_days_operating'),
            'total_number_battery_over_discharges' => $request->get('total_number_battery_over_discharges') ?? $request->get('historical_total_number_battery_over_discharges'),
            'total_number_battery_full_charges' => $request->get('total_number_battery_full_charges') ?? $request->get('historical_total_number_battery_full_charges'),
            'total_charging_amp_hours' => $request->get('total_charging_amp_hours') ?? $request->get('historical_total_charging_amp_hours'),
            'total_discharging_amp_hours' => $request->get('total_discharging_amp_hours') ?? $request->get('historical_total_discharging_amp_hours'),
            'cumulative_power_generation' => $request->get('cumulative_power_generation') ?? $request->get('historical_cumulative_power_generation'),
            'cumulative_power_consumption' => $request->get('cumulative_power_consumption') ?? $request->get('historical_cumulative_power_consumption'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
