<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Request;

use function array_filter;

/**
 * Class HardwarePowerGenerator
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric|null $battery_voltage Voltaje de la batería
 * @property numeric|null $battery_temperature Temperatura de la batería en su exterior (sensor externo a la batería)
 * @property int|null $battery_percentage Porcentaje de carga en la batería (0-100)
 * @property int|null $charging_status El modo de cargar batería en el momento, código interno del fabricante (1,2,3...).
 * @property string|null $charging_status_label El modo de cargar batería en el momento. (deactivated, activated, mppt, equalizing, boost, floating, current limiting)
 * @property numeric|null $amperage La intensidad de carga que está generando el generador.
 * @property numeric|null $voltage El voltaje de carga que está generando el generador.
 * @property numeric|null $power La potencia de carga que está generando el generador.
 * @property bool|null $light_status Indica si hay luz de calle mediante booleano 0|1.
 * @property int|null $light_brightness Devuelve el porcentaje brillo de la luz de calle (0-100%).
 * @property string|null $read_at Fecha y hora de lectura
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereAmperage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereBatteryPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereBatteryTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereBatteryVoltage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereChargingStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereChargingStatusLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereLightBrightness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereLightStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator wherePower($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwarePowerGenerator whereVoltage($value)
 * @mixin \Eloquent
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
     *
     * @return HardwarePowerGenerator
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
