<?php

namespace App\Models\Hardware;

use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Models\BaseModels\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Request;
use function array_filter;

/**
 * Class HardwarePowerGenerator
 */
class HardwarePowerGenerator extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_generators';

    protected $fillable = ['hardware_device_id', 'battery_voltage', 'battery_temperature',
        'battery_percentage', 'charging_status', 'charging_status_label', 'amperage',
        'voltage', 'power', 'light_status', 'light_brightness', 'read_at'];


    /**
     * Prepara el modelo para ser guardado a partir de los datos de una
     * request.
     *
     * @param \App\Models\Hardware\HardwareDevice $device
     * @param                                     $request
     *
     * @return \App\Models\Hardware\HardwarePowerGenerator
     */
    public static function createModel(HardwareDevice $device, $request)
    {
        $data = [
            'hardware_device_id' => $device->id,
            'battery_voltage' => $request->get('battery_voltage'),
            'battery_temperature' => $request->get('battery_temperature'),
            'battery_percentage' => $request->get('battery_percentage'),
            'charging_status' => $request->get('energy_charging_status'),
            'charging_status_label' => $request->get('energy_charging_status_label'),
            'amperage' => $request->get('energy_amperage'),
            'voltage' => $request->get('energy_voltage'),
            'power' => $request->get('energy_power'),
            'light_status' => $request->get('street_light_status'),
            'light_brightness' => $request->get('street_light_brightness'),
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
            'battery_voltage' => $request->get('battery_voltage'),
            'battery_temperature' => $request->get('battery_temperature'),
            'battery_percentage' => $request->get('battery_percentage'),
            'charging_status' => $request->get('energy_charging_status'),
            'charging_status_label' => $request->get('energy_charging_status_label'),
            'amperage' => $request->get('energy_amperage'),
            'voltage' => $request->get('energy_voltage'),
            'power' => $request->get('energy_power'),
            'light_status' => $request->get('street_light_status'),
            'light_brightness' => $request->get('street_light_brightness'),
            'read_at' => $request->get('read_at'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
