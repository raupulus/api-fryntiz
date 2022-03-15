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

    protected $fillable = ['hardware_device_id', 'days_operating',
        'number_battery_over_discharges', 'number_battery_full_charges',
        'amperage', 'power', 'read_at'];


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
            'days_operating' => $request->get('days_operating'),
            'number_battery_over_discharges' => $request->get('number_battery_over_discharges'),
            'number_battery_full_charges' => $request->get('number_battery_full_charges'),
            'amperage' => $request->get('historical_energy_amperage'),
            'power' => $request->get('historical_energy_power'),
            'read_at' => $request->get('read_at'),
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
            'days_operating' => $request->get('days_operating'),
            'number_battery_over_discharges' => $request->get('number_battery_over_discharges'),
            'number_battery_full_charges' => $request->get('number_battery_full_charges'),
            'amperage' => $request->get('historical_energy_amperage') ?? $this->amperage,
            'power' => $request->get('historical_energy_power') ?? $this->power,
            'read_at' => $request->get('read_at'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
