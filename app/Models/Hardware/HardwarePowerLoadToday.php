<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use App\Traits\SummarisesEnergyDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Resumen del día del consumo de un elemento.
 *
 * Una fila por **elemento y día**. `energy_wh` y `energy_ah` son lo que se suma;
 * los `*_min` y `*_max` son de la magnitud instantánea, que es donde un extremo
 * significa algo.
 *
 * @property int $id
 * @property int|null $hardware_device_id
 * @property int|null $hardware_energy_id
 * @property int|null $fan_min
 * @property int|null $fan_max
 * @property float|null $temperature_min
 * @property float|null $temperature_max
 * @property float|null $voltage_min
 * @property float|null $voltage_max
 * @property float|null $amperage_min
 * @property float|null $amperage_max
 * @property float|null $power_min
 * @property float|null $power_max
 * @property float|null $battery_min
 * @property float|null $battery_max
 * @property int|null $battery_percentage_min
 * @property int|null $battery_percentage_max
 * @property float|null $energy_wh
 * @property float|null $energy_ah
 * @property int $readings_count
 * @property Carbon|null $date
 * @property Carbon|null $read_at
 *
 * @method static Builder<static>|HardwarePowerLoadToday newModelQuery()
 * @method static Builder<static>|HardwarePowerLoadToday newQuery()
 * @method static Builder<static>|HardwarePowerLoadToday query()
 *
 * @mixin \Eloquent
 */
class HardwarePowerLoadToday extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;
    use SummarisesEnergyDay;

    protected $table = 'hardware_power_loads_today';

    protected $fillable = [
        'hardware_device_id', 'hardware_energy_id',
        'fan_min', 'fan_max', 'temperature_min', 'temperature_max',
        'voltage_min', 'voltage_max', 'amperage_min', 'amperage_max',
        'power_min', 'power_max', 'battery_min', 'battery_max',
        'battery_percentage_min', 'battery_percentage_max',
        'energy_wh', 'energy_ah', 'readings_count', 'date', 'read_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'fan_min' => 'integer',
        'fan_max' => 'integer',
        'temperature_min' => 'float',
        'temperature_max' => 'float',
        'voltage_min' => 'float',
        'voltage_max' => 'float',
        'amperage_min' => 'float',
        'amperage_max' => 'float',
        'power_min' => 'float',
        'power_max' => 'float',
        'battery_min' => 'float',
        'battery_max' => 'float',
        'battery_percentage_min' => 'integer',
        'battery_percentage_max' => 'integer',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'readings_count' => 'integer',
        'date' => 'date',
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
            'battery_percentage' => ['min' => 'battery_percentage_min', 'max' => 'battery_percentage_max'],
        ];
    }
}
