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
 * Acumulado histórico de la generación de un elemento.
 *
 * `number_battery_over_discharges` y `number_battery_full_charges` no salen de
 * aquí: los declara el controlador solar y los escribe quien recibe su lectura.
 * Por eso el recálculo desde los resúmenes diarios no los toca.
 *
 * @property int $id
 * @property int|null $hardware_device_id
 * @property int|null $hardware_energy_id
 * @property int|null $days_operating Días distintos con lecturas
 * @property int|null $number_battery_over_discharges
 * @property int|null $number_battery_full_charges
 * @property float|null $energy_wh
 * @property float|null $energy_ah
 * @property int $readings_count
 * @property Carbon|null $read_at
 *
 * @method static Builder<static>|HardwarePowerGeneratorHistorical newModelQuery()
 * @method static Builder<static>|HardwarePowerGeneratorHistorical newQuery()
 * @method static Builder<static>|HardwarePowerGeneratorHistorical query()
 *
 * @mixin \Eloquent
 */
class HardwarePowerGeneratorHistorical extends BaseModel
{
    use AccumulatesEnergyHistory;
    use BelongsToHardwareDevice;
    use HasFactory;

    protected $table = 'hardware_power_generators_historical';

    protected $fillable = [
        'hardware_device_id', 'hardware_energy_id', 'days_operating',
        'number_battery_over_discharges', 'number_battery_full_charges',
        'energy_wh', 'energy_ah', 'readings_count', 'read_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'days_operating' => 'integer',
        'number_battery_over_discharges' => 'integer',
        'number_battery_full_charges' => 'integer',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'readings_count' => 'integer',
        'read_at' => 'datetime',
    ];

    /**
     * Esta tabla no guarda extremos: sólo contadores y acumulados.
     *
     * @return array<string, array{min?: string, max?: string}>
     */
    protected static function extremeColumns(): array
    {
        return [];
    }

    /**
     * @return class-string
     */
    protected static function todayModel(): string
    {
        return HardwarePowerGeneratorToday::class;
    }
}
