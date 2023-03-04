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

    protected $fillable = ['hardware_device_id', 'fan', 'temperature', 'voltage', 'amperage',
        'power', 'battery_voltage', 'battery_percentage', 'read_at'];

    /**
     * Prepara el modelo para ser guardado a partir de los datos de una
     * request.
     *
     * @param \App\Models\Hardware\HardwareDevice $device
     * @param                                     $request
     *
     * @return \App\Models\Hardware\HardwarePowerLoad
     */
    public static function createModel(HardwareDevice $device, $request): HardwarePowerLoad
    {
        $fan = $request->get('fan') ?? $request->get('load_fan');
        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');

        if (! $amperage) {
            $amperage = $power / $voltage;
        }

        if (! $voltage) {
            $voltage = $power / $amperage;
        }

        if (! $power) {
            $power = $amperage * $voltage;
        }

        $data = [
            'hardware_device_id' => $device->id,
            'fan' => $fan,
            'temperature' => $request->get('temperature'),
            'voltage' => $voltage,
            'amperage' => $amperage,
            'power' => $power,
            'battery_voltage' => $request->get('battery_voltage'),
            'battery_percentage' => $request->get('battery_percentage'),
            'read_at' => $request->get('read_at') ?? Carbon::now(),
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
        $fan = $request->get('fan') ?? $request->get('load_fan');
        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');

        if (! $amperage) {
            $amperage = $power / $voltage;
        }

        if (! $voltage) {
            $voltage = $power / $amperage;
        }

        if (! $power) {
            $power = $amperage * $voltage;
        }

        $data = [
            'fan' => $fan,
            'temperature' => $request->get('temperature'),
            'voltage' => $voltage,
            'amperage' => $amperage,
            'power' => $power,
            'battery_voltage' => $request->get('battery_voltage'),
            'battery_percentage' => $request->get('battery_percentage'),
            'read_at' => $request->get('read_at') ?? Carbon::now(),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
