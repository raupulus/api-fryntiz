<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use function array_filter;

/**
 * Class HardwarePowerLoadHistorical
 */
class HardwarePowerLoadHistorical extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_loads_historical';

    protected $fillable = ['hardware_device_id', 'days_operating',
        'fan_min', 'fan_max', 'temperature_min', 'temperature_max',
        'voltage_min', 'voltage_max', 'power_min', 'power_max', 'power',
        'battery_min', 'battery_max', 'amperage_min', 'amperage_max', 'amperage',
        'read_at'];


    /**
     * Prepara el modelo para ser guardado a partir de los datos de una
     * request.
     *
     * @param \App\Models\Hardware\HardwareDevice $device
     * @param                                     $request
     *
     * @return self
     */
    public static function createModel(HardwareDevice $device, $request): self
    {
        $fan = $request->get('load_fan');
        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');
        $temperature = $request->get('temperature');
        $battery = $request->get('battery_voltage');

        $data = [
            'hardware_device_id' => $device->id,
            'days_operating' => $request->get('days_operating'),
            'read_at' => $request->get('read_at') ?? Carbon::now(),
            'fan_min' => $fan,
            'fan_max' => $fan,
            'temperature_min' => $temperature,
            'temperature_max' => $temperature,
            'voltage_min' => $voltage,
            'voltage_max' => $voltage,
            'battery_min' => $request->get('battery_min_voltage') ?? $battery,
            'battery_max' => $request->get('battery_max_voltage') ?? $battery,
            'amperage_min' => $amperage,
            'amperage_max' => $request->get('today_load_amperage_max') ?? $amperage,
            'amperage' => $request->get('historical_load_amperage'),
            'power_min' => $power,
            'power_max' => $power,
            'power' => $request->get('historical_load_power'),
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
    public function updateModel($request): self
    {
        $fan = $request->get('load_fan');
        $fanMin = $fan < $this->fan_min ? $fan : $this->fan_min;
        $fanMax = $fan > $this->fan_max ? $fan : $this->fan_max;
        $temperature = $request->get('temperature');
        $temperatureMin = $temperature < $this->temperature_min ? $temperature : $this->temperature_min;
        $temperatureMax = $temperature > $this->temperature_max ? $temperature : $this->temperature_max;

        $voltage = $request->get('voltage') ?? $request->get('load_voltage');
        $amperage = $request->get('amperage') ?? $request->get('load_amperage');
        $power = $request->get('power') ?? $request->get('load_power');
        $battery = $request->get('battery_voltage');

        if (! $amperage && $power && $voltage) {
            $amperage = $power / $voltage;
        }

        if (! $voltage && $power && $amperage) {
            $voltage = $power / $amperage;
        }

        if (! $power && $amperage && $voltage) {
            $power = $amperage * $voltage;
        }

        $amperageTotal = $request->get('historical_load_amperage') ?? $this->amperage;
        $amperageMin = $amperage < $this->amperage_min ? $amperage : $this->amperage_min;
        $amperageMax = $amperage > $this->amperage_max ? $amperage : $this->amperage_max;

        $powerTotal = $request->get('historical_load_power') ?? $this->power;
        $powerMin = $power < $this->power_min ? $power : $this->power_min;
        $powerMax = $power > $this->power_max ? $power : $this->power_max;

        $voltageMin = $voltage < $this->voltage_min ? $voltage : $this->voltage_min;
        $voltageMax = $voltage > $this->voltage_max ? $voltage : $this->voltage_max;

        $batteryMinCheck = $request->get('battery_min_voltage') ?? $battery;
        $batteryMin = $batteryMinCheck < $this->battery_min_voltage ? $batteryMinCheck : $this->battery_min_voltage;
        $batteryMaxCheck = $request->get('battery_max_voltage') ?? $battery;
        $batteryMax = $batteryMaxCheck > $this->battery_max_voltage ? $batteryMaxCheck : $this->battery_max_voltage;

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
            'amperage_min' => $amperageMin,
            'amperage_max' => $amperageMax,
            'amperage' => $amperageTotal,
            'power_min' => $powerMin,
            'power_max' => $powerMax,
            'power' => $powerTotal,
            'days_operating' => $request->get('days_operating') ?? $this->days_operating,
        ];

        $this->fill(array_filter($data, 'strlen'));

        return $this;
    }











    public static function calculateHistoricalFromTodays(int $hardwareDeviceId): ?self
    {
        $historical = self::where('hardware_device_id', $hardwareDeviceId)
            ->orderByDesc('updated_at')
            ->first();

        $now = Carbon::now();

        $dataToday = HardwarePowerLoadToday::select([
            DB::raw('count(id) as days_operating'),
            DB::raw('min(fan_min) as fan_min'),
            DB::raw('max(fan_max) as fan_max'),
            DB::raw('min(temperature_min) as temperature_min'),
            DB::raw('max(temperature_max) as temperature_max'),
            DB::raw('min(voltage_min) as voltage_min'),
            DB::raw('max(voltage_max) as voltage_max'),
            DB::raw('min(battery_min) as battery_min'),
            DB::raw('max(battery_max) as battery_max'),
            DB::raw('min(amperage_min) as amperage_min'),
            DB::raw('max(amperage_max) as amperage_max'),
            DB::raw('sum(amperage) as amperage'),
            DB::raw('min(power_min) as power_min'),
            DB::raw('max(power_max) as power_max'),
            DB::raw('sum(power) as power'),
        ])
            ->where('hardware_device_id', $hardwareDeviceId)
            ->first()
            ->toArray();

        if ($historical) {
            $historical->update(array_merge([
                'read_at' => $now,
            ], $dataToday));
        } else {
            $historical = self::create(array_merge([
                'hardware_device_id' => $hardwareDeviceId,
                'read_at' => $now,
            ], $dataToday));
        }

        return $historical;
    }
}
