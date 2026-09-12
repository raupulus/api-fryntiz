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
 * Acumulado total histórico de energía por sesión de encendido (D115, Fase 3).
 *
 * Si el odómetro del hardware se reinicia a 0, se abre una nueva sesión
 * (session_index + 1) para preservar la serie histórica íntegra.
 *
 * @property int $id
 * @property int $hardware_device_id Dispositivo al que pertenece la serie
 * @property int|null $hardware_energy_id Elemento concreto (canal/rol)
 * @property int $session_index Número de sesión de odómetro
 * @property int $days_operating Días acumulados en esta sesión
 * @property int $readings_count Lecturas acumuladas en esta sesión
 * @property float $energy_wh Total acumulado de energía en esta sesión (Wh)
 * @property float $energy_ah Total acumulado de amperios-hora en esta sesión (Ah)
 * @property int|null $number_battery_full_charges Ciclos de carga completa acumulados
 * @property int|null $number_battery_over_discharges Ciclos de sobredescarga acumulados
 * @property float|null $voltage_min Tensión mínima histórica en la sesión (V)
 * @property float|null $voltage_max Tensión máxima histórica en la sesión (V)
 * @property float|null $amperage_min Corriente mínima histórica en la sesión (A)
 * @property float|null $amperage_max Corriente máxima histórica en la sesión (A)
 * @property float|null $power_min Potencia mínima histórica en la sesión (W)
 * @property float|null $power_max Potencia máxima histórica en la sesión (W)
 * @property float|null $temperature_min Temperatura mínima histórica en la sesión (°C)
 * @property float|null $temperature_max Temperatura máxima histórica en la sesión (°C)
 * @property float|null $battery_min Tensión mínima de batería histórica (V)
 * @property float|null $battery_max Tensión máxima de batería histórica (V)
 * @property int|null $fan_min Velocidad mínima histórica del ventilador
 * @property int|null $fan_max Velocidad máxima histórica del ventilador
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice $hardwareDevice
 * @property-read HardwareEnergy|null $hardwareEnergy
 * @property-read HardwareEnergy|null $energy
 *
 * @method static Builder<static>|HardwareEnergyHistorical newModelQuery()
 * @method static Builder<static>|HardwareEnergyHistorical newQuery()
 * @method static Builder<static>|HardwareEnergyHistorical query()
 * @method static Builder<static>|HardwareEnergyHistorical forDevice(int $deviceId)
 * @method static Builder<static>|HardwareEnergyHistorical forElement(int $elementId)
 * @method static Builder<static>|HardwareEnergyHistorical latestSession(?int $elementId = null)
 *
 * @mixin \Eloquent
 */
class HardwareEnergyHistorical extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;

    protected $table = 'hardware_energy_historical';

    protected $fillable = [
        'hardware_device_id',
        'hardware_energy_id',
        'session_index',
        'days_operating',
        'readings_count',
        'energy_wh',
        'energy_ah',
        'number_battery_full_charges',
        'number_battery_over_discharges',
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
        'fan_min',
        'fan_max',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'session_index' => 'integer',
        'days_operating' => 'integer',
        'readings_count' => 'integer',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'number_battery_full_charges' => 'integer',
        'number_battery_over_discharges' => 'integer',
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
        'fan_min' => 'integer',
        'fan_max' => 'integer',
    ];

    // ─────────────────────────── Relaciones ────────────────────────────

    /**
     * Elemento energético al que corresponde la serie histórica.
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
     * Ordena por la sesión más reciente.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeLatestSession(Builder $query, ?int $elementId = null): Builder
    {
        return $query
            ->when($elementId !== null, static fn (Builder $q) => $q->where('hardware_energy_id', $elementId))
            ->orderByDesc('session_index');
    }

    // ───────────────────────────── Dominio ─────────────────────────────

    /**
     * Acumula una lectura en el histórico, gestionando sesiones en caso de reset de odómetro.
     *
     * @param  array<string, mixed>  $data
     */
    public static function accumulateForElement(
        int $deviceId,
        ?int $elementId,
        array $data
    ): static {
        /** @var static|null $latest */
        $latest = static::query()
            ->where('hardware_device_id', $deviceId)
            ->when(
                $elementId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $elementId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
            )
            ->orderByDesc('session_index')
            ->first();

        $reportedWh = isset($data['historical_energy_wh'])
            ? (float) $data['historical_energy_wh']
            : (isset($data['total_energy_wh']) ? (float) $data['total_energy_wh'] : null);

        $reportedAh = isset($data['historical_energy_ah'])
            ? (float) $data['historical_energy_ah']
            : (isset($data['total_energy_ah']) ? (float) $data['total_energy_ah'] : null);

        // Detección de reinicio de odómetro: si el total reportado cae significativamente por debajo del acumulado previo
        $isReset = false;
        if ($latest !== null) {
            if ($reportedWh !== null && $latest->energy_wh > 50.0 && $reportedWh < ($latest->energy_wh * 0.5)) {
                $isReset = true;
            } elseif ($reportedAh !== null && $latest->energy_ah > 50.0 && $reportedAh < ($latest->energy_ah * 0.5)) {
                $isReset = true;
            }
        }

        if ($latest === null) {
            $record = static::query()->make([
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => $elementId,
                'session_index' => 1,
                'days_operating' => 1,
                'readings_count' => 0,
                'energy_wh' => 0.0,
                'energy_ah' => 0.0,
            ]);
        } elseif ($isReset) {
            $record = static::query()->make([
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => $elementId,
                'session_index' => $latest->session_index + 1,
                'days_operating' => 1,
                'readings_count' => 0,
                'energy_wh' => 0.0,
                'energy_ah' => 0.0,
            ]);
        } else {
            $record = $latest;
        }

        // Actualizar extremos
        $record->updateExtremes($data);

        // Actualizar energía Wh
        if ($reportedWh !== null) {
            $record->energy_wh = max((float) $record->energy_wh, $reportedWh);
        } elseif (isset($data['energy_wh'])) {
            $record->energy_wh = (float) $record->energy_wh + (float) $data['energy_wh'];
        }

        // Actualizar energía Ah
        if ($reportedAh !== null) {
            $record->energy_ah = max((float) $record->energy_ah, $reportedAh);
        } elseif (isset($data['energy_ah'])) {
            $record->energy_ah = (float) $record->energy_ah + (float) $data['energy_ah'];
        }

        // Ciclos de batería si vienen dados
        if (isset($data['battery_full_charges'])) {
            $record->number_battery_full_charges = max(
                (int) ($record->number_battery_full_charges ?? 0),
                (int) $data['battery_full_charges']
            );
        }

        if (isset($data['battery_over_discharges'])) {
            $record->number_battery_over_discharges = max(
                (int) ($record->number_battery_over_discharges ?? 0),
                (int) $data['battery_over_discharges']
            );
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
