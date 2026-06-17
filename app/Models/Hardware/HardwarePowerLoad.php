<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use function array_filter;

/**
 * Class HardwarePowerLoad
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property int|null $fan Velocidad del ventilador en RPM, EJ:3812. Será null si no hay ventilador
 * @property numeric|null $temperature Temperatura del dispositivo en grados centígrados, EJ:38.31.
 * @property numeric|null $voltage Voltaje de la batería en voltios, EJ:12.37.
 * @property float|null $amperage Intensidad de la batería en ah, EJ:1.54.
 * @property float|null $power Potencia de la batería en watios, EJ:13.81.
 * @property numeric|null $battery_voltage Voltaje de la batería. Será 0 por defecto
 * @property int|null $battery_percentage Porcentaje de carga en la batería (0-100)
 * @property string|null $read_at Fecha y hora de lectura
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereAmperage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereBatteryPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereBatteryVoltage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereFan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad wherePower($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerLoad whereVoltage($value)
 *
 * @mixin \Eloquent
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
