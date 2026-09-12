<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Resumen del día de energía para un elemento (D115, Fase 3).
 *
 * Exactamente una fila por elemento y fecha. Unifica los resúmenes diarios
 * de generadores y consumos. La marca de última actualización es updated_at.
 *
 * @property int $id
 * @property int $hardware_device_id Dispositivo al que pertenece la agregación
 * @property int|null $hardware_energy_id Elemento concreto (canal/rol)
 * @property Carbon $date Fecha del día (Y-m-d)
 * @property int $readings_count Número de lecturas agregadas en el día
 * @property float $energy_wh Total de energía acumulada hoy (Wh)
 * @property float $energy_ah Total de amperios-hora transferidos hoy (Ah)
 * @property float|null $voltage_min Tensión mínima hoy (V)
 * @property float|null $voltage_max Tensión máxima hoy (V)
 * @property float|null $amperage_min Corriente mínima hoy (A)
 * @property float|null $amperage_max Corriente máxima hoy (A)
 * @property float|null $power_min Potencia mínima hoy (W)
 * @property float|null $power_max Potencia máxima hoy (W)
 * @property float|null $temperature_min Temperatura mínima hoy (°C)
 * @property float|null $temperature_max Temperatura máxima hoy (°C)
 * @property float|null $battery_min Tensión mínima de batería hoy (V)
 * @property float|null $battery_max Tensión máxima de batería hoy (V)
 * @property int|null $battery_percentage_min SOC mínimo de batería hoy (%)
 * @property int|null $battery_percentage_max SOC máximo de batería hoy (%)
 * @property int|null $fan_min Velocidad o estado mínimo del ventilador hoy
 * @property int|null $fan_max Velocidad o estado máximo del ventilador hoy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice $hardwareDevice
 * @property-read HardwareEnergy|null $hardwareEnergy
 * @property-read HardwareEnergy|null $energy
 *
 * @method static Builder<static>|HardwareEnergyToday newModelQuery()
 * @method static Builder<static>|HardwareEnergyToday newQuery()
 * @method static Builder<static>|HardwareEnergyToday query()
 * @method static Builder<static>|HardwareEnergyToday forDevice(int $deviceId)
 * @method static Builder<static>|HardwareEnergyToday forElement(int $elementId)
 * @method static Builder<static>|HardwareEnergyToday forDate(string $date)
 *
 * @mixin \Eloquent
 */
class HardwareEnergyToday extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;

    protected $table = 'hardware_energy_today';

    protected $fillable = [
        'hardware_device_id',
        'hardware_energy_id',
        'date',
        'readings_count',
        'energy_wh',
        'energy_ah',
        'voltage_min',
        'voltage_max',
        'amperage_min',
        'amperage_max',
        'power_min',
        'power_max',
        'temperature_min',
        'temperature_max',
        'battery_min',
        'battery_max',
        'battery_percentage_min',
        'battery_percentage_max',
        'fan_min',
        'fan_max',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'date' => 'date',
        'readings_count' => 'integer',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'voltage_min' => 'float',
        'voltage_max' => 'float',
        'amperage_min' => 'float',
        'amperage_max' => 'float',
        'power_min' => 'float',
        'power_max' => 'float',
        'temperature_min' => 'float',
        'temperature_max' => 'float',
        'battery_min' => 'float',
        'battery_max' => 'float',
        'battery_percentage_min' => 'integer',
        'battery_percentage_max' => 'integer',
        'fan_min' => 'integer',
        'fan_max' => 'integer',
    ];

    // ─────────────────────────── Relaciones ────────────────────────────

    /**
     * Elemento energético al que corresponde el resumen.
     */
    public function hardwareEnergy(): BelongsTo
    {
        return $this->belongsTo(HardwareEnergy::class, 'hardware_energy_id');
    }

    /**
     * Alias de hardwareEnergy por ergonomía y retrocompatibilidad.
     */
    public function energy(): BelongsTo
    {
        return $this->hardwareEnergy();
    }

    // ───────────────────────────── Scopes ──────────────────────────────

    /**
     * Filtra por elemento energético.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForElement(Builder $query, int $elementId): Builder
    {
        return $query->where('hardware_energy_id', $elementId);
    }

    /**
     * Filtra por fecha concreta.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('date', $date);
    }

    // ───────────────────────────── Dominio ─────────────────────────────

    /**
     * Actualiza o crea el resumen del día para un elemento con los datos de una lectura.
     *
     * Si el dispositivo provee su propio total del día (device_energy_wh / today_energy_wh),
     * éste prevalece y sustituye. Si no, se incrementa acumulando delta Wh/Ah.
     *
     * @param  array<string, mixed>  $data
     */
    public static function recalculateForElement(
        int $deviceId,
        ?int $elementId,
        array $data,
        ?string $date = null
    ): static {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        /** @var static $record */
        $record = static::query()
            ->where('hardware_device_id', $deviceId)
            ->when(
                $elementId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $elementId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
            )
            ->where('date', $date)
            ->first() ?? static::query()->make([
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => $elementId,
                'date' => $date,
                'readings_count' => 0,
                'energy_wh' => 0.0,
                'energy_ah' => 0.0,
            ]);

        // Actualizar extremos
        $record->updateExtremes($data);

        // Actualizar energía Wh
        if (isset($data['today_energy_wh'])) {
            $record->energy_wh = (float) $data['today_energy_wh'];
        } elseif (isset($data['device_energy_wh'])) {
            $record->energy_wh = (float) $data['device_energy_wh'];
        } elseif (isset($data['energy_wh'])) {
            $record->energy_wh = (float) $record->energy_wh + (float) $data['energy_wh'];
        }

        // Actualizar energía Ah
        if (isset($data['today_energy_ah'])) {
            $record->energy_ah = (float) $data['today_energy_ah'];
        } elseif (isset($data['device_energy_ah'])) {
            $record->energy_ah = (float) $data['device_energy_ah'];
        } elseif (isset($data['energy_ah'])) {
            $record->energy_ah = (float) $record->energy_ah + (float) $data['energy_ah'];
        }

        $record->readings_count = (int) $record->readings_count + 1;
        $record->save();

        return $record;
    }

    /**
     * Ajusta los mínimos y máximos a partir de los datos entrantes.
     *
     * @param  array<string, mixed>  $data
     */
    protected function updateExtremes(array $data): void
    {
        $mapping = [
            'voltage' => ['voltage_min', 'voltage_max'],
            'amperage' => ['amperage_min', 'amperage_max'],
            'power' => ['power_min', 'power_max'],
            'temperature' => ['temperature_min', 'temperature_max'],
            'battery_voltage' => ['battery_min', 'battery_max'],
            'battery' => ['battery_min', 'battery_max'],
        ];

        foreach ($mapping as $inputKey => [$minCol, $maxCol]) {
            if (! isset($data[$inputKey]) || $data[$inputKey] === '') {
                continue;
            }

            $floatVal = (float) $data[$inputKey];
            if ($this->{$minCol} === null || $floatVal < (float) $this->{$minCol}) {
                $this->{$minCol} = $floatVal;
            }
            if ($this->{$maxCol} === null || $floatVal > (float) $this->{$maxCol}) {
                $this->{$maxCol} = $floatVal;
            }
        }

        if (isset($data['battery_percentage']) && $data['battery_percentage'] !== '') {
            $batVal = (int) $data['battery_percentage'];
            if ($this->battery_percentage_min === null || $batVal < $this->battery_percentage_min) {
                $this->battery_percentage_min = $batVal;
            }
            if ($this->battery_percentage_max === null || $batVal > $this->battery_percentage_max) {
                $this->battery_percentage_max = $batVal;
            }
        }

        if (isset($data['fan']) && $data['fan'] !== '') {
            $fanVal = (int) $data['fan'];
            if ($this->fan_min === null || $fanVal < $this->fan_min) {
                $this->fan_min = $fanVal;
            }
            if ($this->fan_max === null || $fanVal > $this->fan_max) {
                $this->fan_max = $fanVal;
            }
        }
    }
}
