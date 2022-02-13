<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use function array_filter;

/**
 * Class HardwarePowerLoad
 */
class HardwarePowerLoad extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_loads';

    protected $fillable = ['hardware_device_id', 'fan', 'temperature', 'voltage', 'amperage', 'created_at'];

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
            'fan' => $request->get('fan'),
            'temperature' => $request->get('temperature') ?? $request->get('controller_temperature'),
            'voltage' => $request->get('voltage') ?? $request->get('system_voltage_current'),
            'amperage' => $request->get('amperage') ?? $request->get('system_intensity_current'),
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
        $data = [
            'fan' => $request->get('fan'),
            'temperature' => $request->get('temperature') ?? $request->get('controller_temperature'),
            'voltage' => $request->get('voltage') ?? $request->get('system_voltage_current'),
            'amperage' => $request->get('amperage') ?? $request->get('system_intensity_current'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
