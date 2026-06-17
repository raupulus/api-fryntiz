<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use function array_filter;

/**
 * Class HardwarePowerGeneratorHistorical
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property int|null $days_operating Número de días que el dispositivo ha estado operativo
 * @property int|null $number_battery_over_discharges Número de veces que se ha vaciado la batería por completo
 * @property int|null $number_battery_full_charges Número de veces que se ha cargado la batería por completo
 * @property float|null $amperage Carga total en Ah que ha sido almacenado en la batería
 * @property numeric|null $power Potencia (W) generada acumulada en el tiempo
 * @property string|null $read_at Fecha y hora de lectura
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereAmperage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereDaysOperating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereNumberBatteryFullCharges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereNumberBatteryOverDischarges($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical wherePower($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorHistorical whereUpdatedAt($value)
 * @mixin \Eloquent
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
     *
     * @return HardwarePowerGeneratorHistorical
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
     *
     * @return $this
     */
    public function updateModel($request)
    {
        $amperage = $request->get('historical_energy_amperage') ?? $this->amperage;
        $power = $request->get('historical_energy_power') ?? $this->power;

        $data = [
            'days_operating' => $request->get('days_operating') ?? $this->days_operating,
            'number_battery_over_discharges' => $request->get('number_battery_over_discharges') ?? $this->number_battery_over_discharges,
            'number_battery_full_charges' => $request->get('number_battery_full_charges') ?? $this->number_battery_full_charges,
            'amperage' => ($amperage > $this->amperage) ? $amperage : $this->amperage,
            'power' => ($power > $this->power) ? $power : $this->power,
            'read_at' => $request->get('read_at'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
