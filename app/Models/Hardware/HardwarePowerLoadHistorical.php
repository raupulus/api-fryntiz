<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\AccumulatesEnergyHistory;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Acumulado histórico del consumo de un elemento.
 *
 * Se recalcula entero desde los resúmenes diarios, así que no hay estado que se
 * pueda corromper: si un día se arregla un cálculo, se relanza y queda bien.
 *
 * @property int $id
 * @property int|null $hardware_device_id
 * @property int|null $hardware_energy_id
 * @property int|null $days_operating Días distintos con lecturas
 * @property int|null $fan_min
 * @property int|null $fan_max
 * @property float|null $temperature_min
 * @property float|null $temperature_max
 * @property float|null $voltage_min
 * @property float|null $voltage_max
 * @property float|null $battery_min
 * @property float|null $battery_max
 * @property float|null $amperage_min
 * @property float|null $amperage_max
 * @property float|null $power_min
 * @property float|null $power_max
 * @property float|null $energy_wh
 * @property float|null $energy_ah
 * @property int $readings_count
 * @property Carbon|null $read_at
 *
 * @method static Builder<static>|HardwarePowerLoadHistorical newModelQuery()
 * @method static Builder<static>|HardwarePowerLoadHistorical newQuery()
 * @method static Builder<static>|HardwarePowerLoadHistorical query()
 *
 * @mixin \Eloquent
 */
class HardwarePowerLoadHistorical extends BaseModel
{
    use AccumulatesEnergyHistory;
    use BelongsToHardwareDevice;
    use HasFactory;

    protected $table = 'hardware_power_loads_historical';

    protected $fillable = [
        'hardware_device_id', 'hardware_energy_id', 'days_operating',
        'fan_min', 'fan_max', 'temperature_min', 'temperature_max',
        'voltage_min', 'voltage_max', 'battery_min', 'battery_max',
        'amperage_min', 'amperage_max', 'power_min', 'power_max',
        'energy_wh', 'energy_ah', 'readings_count', 'read_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'days_operating' => 'integer',
        'fan_min' => 'integer',
        'fan_max' => 'integer',
        'temperature_min' => 'float',
        'temperature_max' => 'float',
        'voltage_min' => 'float',
        'voltage_max' => 'float',
        'battery_min' => 'float',
        'battery_max' => 'float',
        'amperage_min' => 'float',
        'amperage_max' => 'float',
        'power_min' => 'float',
        'power_max' => 'float',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'readings_count' => 'integer',
        'read_at' => 'datetime',
    ];

    /**
     * @return array<string, array{min?: string, max?: string}>
     */
    protected static function extremeColumns(): array
    {
        return [
            'fan' => ['min' => 'fan_min', 'max' => 'fan_max'],
            'temperature' => ['min' => 'temperature_min', 'max' => 'temperature_max'],
            'voltage' => ['min' => 'voltage_min', 'max' => 'voltage_max'],
            'amperage' => ['min' => 'amperage_min', 'max' => 'amperage_max'],
            'power' => ['min' => 'power_min', 'max' => 'power_max'],
            'battery' => ['min' => 'battery_min', 'max' => 'battery_max'],
        ];
    }

    /**
     * @return class-string
     */
    protected static function todayModel(): string
    {
        return HardwarePowerLoadToday::class;
    }
}
