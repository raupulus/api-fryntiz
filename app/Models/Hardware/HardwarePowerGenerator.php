<?php

namespace App\Models\Hardware;

use App\Http\Requests\Api\Hardware\V1\StoreSolarChargeRequest;
use App\Models\BaseModels\BaseModel;
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

    protected $fillable = ['hardware_device_id', 'load_current', 'load_voltage',
        'load_power', 'battery_voltage', 'battery_temperature', 'battery_percentage',
        'charging_status', 'charging_status_label', 'origin_current', 'origin_voltage',
        'origin_power', 'light_status', 'light_brightness'];


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
            'load_current' => $request->get('load_current'),
            'load_voltage' => $request->get('load_voltage'),
            'load_power' => $request->get('load_power'),
            'battery_voltage' => $request->get('battery_voltage'),
            'battery_temperature' => $request->get('battery_temperature'),
            'battery_percentage' => $request->get('battery_percentage'),
            'charging_status' => $request->get('charging_status'),
            'charging_status_label' => $request->get('charging_status_label'),
            'origin_current' => $request->get('origin_current') ?? $request->get('solar_current'),
            'origin_voltage' => $request->get('origin_voltage') ?? $request->get('solar_voltage'),
            'origin_power' => $request->get('origin_power') ?? $request->get('solar_power'),
            'light_status' => $request->get('light_status') ?? $request->get('street_light_status'),
            'light_brightness' => $request->get('light_brightness') ?? $request->get('street_light_brightness'),
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
            'hardware_device_id' => $request->get('device_id'),
            'load_current' => $request->get('load_current'),
            'load_voltage' => $request->get('load_voltage'),
            'load_power' => $request->get('load_power'),
            'battery_voltage' => $request->get('battery_voltage'),
            'battery_temperature' => $request->get('battery_temperature'),
            'battery_percentage' => $request->get('battery_percentage'),
            'charging_status' => $request->get('charging_status'),
            'charging_status_label' => $request->get('charging_status_label'),
            'origin_current' => $request->get('origin_current') ?? $request->get('solar_current'),
            'origin_voltage' => $request->get('origin_voltage') ?? $request->get('solar_voltage'),
            'origin_power' => $request->get('origin_power') ?? $request->get('solar_power'),
            'light_status' => $request->get('light_status') ?? $request->get('street_light_status'),
            'light_brightness' => $request->get('light_brightness') ?? $request->get('street_light_brightness'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
