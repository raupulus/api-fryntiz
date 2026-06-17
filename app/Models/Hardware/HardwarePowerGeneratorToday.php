<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use function array_filter;

/**
 * Class HardwarePowerGeneratorToday
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric|null $temperature_min Temperatura mínima (°C)
 * @property numeric|null $temperature_max Temperatura máxima (°C)
 * @property numeric|null $voltage_min Volts mínimo (V)
 * @property numeric|null $voltage_max Voltaje máximo (V)
 * @property numeric|null $battery_min Voltaje mínimo de batería (V)
 * @property numeric|null $battery_max Voltaje máximo de batería (V)
 * @property int|null $battery_percentage_min Porcentaje de batería mínimo (%)
 * @property int|null $battery_percentage_max Porcentaje de batería máximo (%)
 * @property float|null $amperage Amperaje total (A)
 * @property numeric|null $amperage_max Máxima carga en amperios (Ah)
 * @property numeric|null $power Potencia total (W)
 * @property numeric|null $power_max Potencia máxima (W)
 * @property string|null $date Fecha de medición
 * @property string|null $read_at Fecha y hora de la última lectura
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereAmperage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereAmperageMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereBatteryMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereBatteryMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereBatteryPercentageMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereBatteryPercentageMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday wherePower($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday wherePowerMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereTemperatureMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereTemperatureMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereVoltageMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGeneratorToday whereVoltageMin($value)
 * @mixin \Eloquent
 */
class HardwarePowerGeneratorToday extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_generators_today';

    protected $fillable = ['hardware_device_id', 'temperature_min', 'temperature_max',
        'voltage_min', 'voltage_max', 'battery_min', 'battery_max', 'battery_percentage_min',
        'battery_percentage_max', 'amperage_max', 'amperage', 'power_max',
        'power', 'date', 'read_at'];

    /**
     * Prepara el modelo para ser guardado a partir de los datos de una
     * request.
     *
     *
     * @return HardwarePowerGeneratorHistorical
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
            'battery_percentage_min' => $request->get('battery_percentage'),
            'battery_percentage_max' => $request->get('battery_percentage'),
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
        $temperature = $request->get('temperature');
        $voltage = $request->get('energy_voltage');
        $batteryMin = $request->get('battery_min_voltage') ?? $request->get('battery_voltage');
        $batteryMax = $request->get('battery_max_voltage') ?? $request->get('battery_voltage');
        $batteryPercentage = $request->get('battery_percentage');

        $data = [
            'date' => $request->get('date'),
            'read_at' => $request->get('read_at'),
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'voltage_min' => $voltage <= $this->voltage_min ? $voltage : $this->voltage_min,
            'voltage_max' => $voltage >= $this->voltage_max ? $voltage : $this->voltage_max,
            'battery_min' => $batteryMin <= $this->battery_min ? $batteryMin : $this->battery_min,
            'battery_max' => $batteryMax >= $this->battery_max ? $batteryMax : $this->battery_max,
            'battery_percentage_min' => $batteryPercentage <= $this->battery_percentage_min ? $batteryPercentage : $this->battery_percentage_min,
            'battery_percentage_max' => $batteryPercentage >= $this->battery_percentage_max ? $batteryPercentage : $this->battery_percentage_max,
            'amperage' => $request->get('today_energy_amperage'),
            'amperage_max' => $request->get('today_energy_amperage_max'),
            'power' => $request->get('today_energy_power'),
            'power_max' => $request->get('today_energy_power_max'),
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }
}
