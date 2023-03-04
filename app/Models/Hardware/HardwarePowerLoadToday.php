<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use function array_filter;

/**
 * Class HardwarePowerLoadToday
 *
 * Model for the hardware power load today table
 *
 * @package App\Models\Hardware
 */
class HardwarePowerLoadToday extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_loads_today';

    protected $fillable = ['hardware_device_id', 'fan_min', 'fan_max',
        'temperature_min', 'temperature_max', 'voltage_min', 'voltage_max',
        'amperage_min', 'amperage_max', 'amperage', 'power_min', 'power_max',
        'power', 'battery_min', 'battery_max', 'battery_percentage_min',
        'battery_percentage_max', 'date', 'read_at'];

    /**
     * Prepara el modelo para ser guardado a partir de los datos de una
     * request.
     *
     * @param \App\Models\Hardware\HardwareDevice $device
     * @param                                     $request
     *
     * @return \App\Models\Hardware\HardwarePowerLoadToday
     */
    public static function createModel(HardwareDevice $device, $request): HardwarePowerLoadToday
    {
        $fan = $request->get('load_fan');
        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');
        $temperature = $request->get('temperature');
        $battery = $request->get('battery_voltage');

        $data = [
            'hardware_device_id' => $device->id,
            'date' => $request->get('date') ?? Carbon::now()->format('Y-m-d'),
            'read_at' => $request->get('read_at') ?? Carbon::now(),
            'fan_min' => $fan,
            'fan_max' => $fan,
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'voltage_min' => $request->get('voltage_min') ?? $voltage,
            'voltage_max' => $request->get('voltage_max') ?? $voltage,
            'battery_min' => $request->get('battery_min_voltage') ?? $battery,
            'battery_max' => $request->get('battery_max_voltage') ?? $battery,
            'battery_percentage_min' => $request->get('battery_percentage'),
            'battery_percentage_max' => $request->get('battery_percentage'),
            'amperage_min' => $request->get('today_load_amperage') ?? $amperage,
            'amperage_max' => $request->get('today_load_amperage_max') ?? $amperage,
            'amperage' => $request->get('today_load_amperage') ?? $amperage,
            'power_min' => $power,
            'power_max' => $power,
            'power' => $request->get('today_load_power') ?? $power,
        ];

        return new self(array_filter($data, 'strlen'));
    }

    /**
     * Prepara el modelo para ser actualizado a partir de los datos de una
     * request.
     *
     * @param $request
     *
     * @return HardwarePowerLoadToday
     */
    public function updateModel($request): HardwarePowerLoadToday
    {
        $fan = $request->get('load_fan');
        $fanMin = ( $fan < $this->fan_min ) ? $fan : $this->fan_min;
        $fanMax = ( $fan > $this->fan_max ) ? $fan : $this->fan_max;
        $temperature = $request->get('temperature');
        $temperatureMin = ( $temperature < $this->temperature_min ) ? $temperature : $this->temperature_min;
        $temperatureMax = ( $temperature > $this->temperature_max ) ? $temperature : $this->temperature_max;

        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');
        $battery = $request->get('battery_voltage');

        if (!$amperage && $power && $voltage) {
            $amperage = $power / $voltage;
        }

        if (!$voltage && $power && $amperage) {
            $voltage = $power / $amperage;
        }

        if (!$power && $amperage && $voltage) {
            $power = $amperage * $voltage;
        }

        $amperageTotal = $request->get('today_load_amperage') ?? $this->amperage;
        $amperageMin = ( $amperage < $this->amperage_min ) ? $amperage : $this->amperage_min;
        $amperageMaxCheck = $request->get('today_load_amperage_max') ?? $amperage;
        $amperageMax = ( $amperageMaxCheck > $this->amperage_max ) ? $amperageMaxCheck : $this->amperage_max;

        $powerTotal = $request->get('today_load_power') ?? $this->power;
        $powerMin = ( $power < $this->power_min ) ? $power : $this->power_min;
        $powerMax = ( $power > $this->power_max ) ? $power : $this->power_max;

        $voltageMin = ( $voltage < $this->voltage_min ) ? $voltage : $this->voltage_min;
        $voltageMax = ( $voltage > $this->voltage_max ) ? $voltage : $this->voltage_max;

        $batteryMinCheck = $request->get('battery_min_voltage') ?? $battery;
        $batteryMin = ( $batteryMinCheck < $this->battery_min_voltage ) ? $batteryMinCheck : $this->battery_min_voltage;
        $batteryMaxCheck = $request->get('battery_max_voltage') ?? $battery;
        $batteryMax = ( $batteryMaxCheck > $this->battery_max_voltage ) ? $batteryMaxCheck : $this->battery_max_voltage;
        $batteryPercentage = $request->get('battery_percentage');

        $data = [
            'read_at' => $request->get('read_at') ?? Carbon::now(),
            'fan_min' => $fanMin,
            'fan_max' => $fanMax,
            'temperature_min' => $temperatureMin,
            'temperature_max' => $temperatureMax,
            'voltage_min' => $voltageMin,
            'voltage_max' => $voltageMax,
            'battery_min' => $batteryMin,
            'battery_max' => $batteryMax,
            'battery_percentage_min' => ( $batteryPercentage <= $this->battery_percentage_min ) ? $batteryPercentage : $this->battery_percentage_min,
            'battery_percentage_max' => ( $batteryPercentage >= $this->battery_percentage_max ) ? $batteryPercentage : $this->battery_percentage_max,
            'amperage_min' => $amperageMin,
            'amperage_max' => $amperageMax,
            'amperage' => ( $amperageTotal > $this->amperage ) ? $amperageTotal : $this->amperage,
            'power_min' => $powerMin,
            'power_max' => $powerMax,
            'power' => ( $powerTotal > $this->power ) ? $powerTotal : $this->power,
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }

    /**
     * Recalcula las estadísticas para el día con los datos de los registros
     * monitorizados recibidos como parámetro.
     *
     * @param int         $hardwareDeviceId Dispositivo asociado a la lectura.
     * @param array       $data             Array con los datos de la lectura:
     *                                      'hardware_device_id',
     *                                      'fan',
     *                                      'power', #opcional (si no amperage,
     *                                      obligatorio)
     *                                      'amperage', #opcional (si no power,
     *                                      obligatorio)
     *                                      'voltage',
     *                                      'temperature',
     *                                      'battery', #(voltage actual de
     *                                      batería)
     *                                      'battery_percentage', #(ver si lo
     *                                      podemos calcular a partir de
     *                                      battery, full 14v?)
     *                                      'read_at', #(si no viene, será el
     *                                      momento actual,
     * @param string|null $date            Fecha de la lectura, Formato Y-m-d.
     *
     * @return static
     */
    public static function recalculateToday(int    $hardwareDeviceId,
                                            array  $data,
                                            string $date = null): ?self
    {


        //TODO: quitar al terminar debug
        if ($hardwareDeviceId === 14) {
            return null;
        }

        $now = Carbon::now();
        $date = $date ?? $now->format('Y-m-d');

        $voltage = $data['voltage'];
        $readAt = isset($data['read_at']) ? $data['read_at'] : $now;
        $fan = isset($data['fan']) ? $data['fan'] : null;
        $power = isset($data['power']) ? $data['power'] : null;
        $amperage = isset($data['amperage']) ? $data['amperage'] : null;
        $temperature = isset($data['temperature']) ? $data['temperature'] : null;
        $battery = isset($data['battery']) ? $data['battery'] : null;
        $batteryPercentage = isset($data['battery_percentage']) ? $data['battery_percentage'] : null;

        if (!$power && $amperage) {
            $power = $voltage * $amperage;
        }

        if (!$amperage && $power) {
            $amperage = $power / $voltage;
        }

        ## TODO: Añadir al modelo de hardware una forma de almacenar tensión de la batería y su máximo/mínimo para poder calcular el porcentaje entre otros cálculos
        if ($battery && !$batteryPercentage) {
            //$batteryFullVoltage = ????
            //$batteryPercentage = $battery / 14 * 100;
        }

        $today = self::where('hardware_device_id', $hardwareDeviceId)
            ->orderByDesc('created_at')
            ->first();

        if ($today) {
            $today->update([
                'fan_min' => $fan < $today->fan_min ? $fan : $today->fan_min,
                'fan_max' => $fan > $today->fan_max ? $fan : $today->fan_max,
                'temperature_min' => $temperature < $today->temperature_min ? $temperature : $today->temperature_min,
                'temperature_max' => $temperature > $today->temperature_max ? $temperature : $today->temperature_max,
                'voltage_min' => $voltage < $today->voltage_min ? $voltage : $today->voltage_min,
                'voltage_max' => $voltage > $today->voltage_max ? $voltage : $today->voltage_max,
                'battery_min' => $battery < $today->battery_min ? $battery : $today->battery_min,
                'batter_max' => $battery > $today->battery_max ? $battery : $today->battery_max,
                'battery_percentage_min' => $batteryPercentage < $today->battery_percentage_min ? $batteryPercentage : $today->battery_percentage_min,
                'battery_percentage_max' => $batteryPercentage > $today->battery_percentage_max ? $batteryPercentage : $today->battery_percentage_max,
                'amperage_min' => $amperage < $today->amperage_min ? $amperage : $today->amperage_min,
                'amperage_max' => $amperage > $today->amperage_max ? $amperage : $today->amperage_max,
                'amperage' => $today->amperage + $amperage,
                'power_min' => $power < $today->power_min ? $power : $today->power_min,
                'power_max' => $power > $today->power_max ? $power : $today->power_max,
                'power' => $today->power + $power,
                'date' => $date,
                'read_at' => $readAt,
            ]);
        } else {
            $today = new self([
                'hardware_device_id' => $hardwareDeviceId,
                'fan_min' => $fan,
                'fan_max' => $fan,
                'temperature_min' => $temperature,
                'temperature_max' => $temperature,
                'voltage_min' => $voltage,
                'voltage_max' => $voltage,
                'battery_min' => $battery,
                'battery_max' => $battery,
                'battery_percentage_min' => $batteryPercentage,
                'battery_percentage_max' => $batteryPercentage,
                'amperage_min' => $amperage,
                'amperage_max' => $amperage,
                'amperage' => $amperage,
                'power_min' => $power,
                'power_max' => $power,
                'power' => $power,
                'date' => $date,
                'read_at' => $readAt,
            ]);

            $today->save();
        }

        return $today;
    }
}
