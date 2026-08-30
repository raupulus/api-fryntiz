<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

use function is_finite;

/**
 * El elemento energético: un panel, un router, una batería (D81).
 *
 * Esta tabla existe desde 2022 y **era ya la entidad que faltaba**, sólo que sin
 * los campos que la hacen útil. Un monitor mide un panel y un router a la vez, y
 * el panel no existía como fila en ningún sitio: no había dónde guardar su
 * tensión ni su tipo. Sin tensión por elemento los vatios salen mal, porque se
 * multiplica la corriente de cada canal por *el único voltaje que trae la
 * petición* —un panel de 24 V y una Pico de 3,7 V en la misma petición dan
 * números sin sentido.
 *
 * `is_generator` sigue existiendo y se rellena desde `role` (D70): primero se
 * migran los datos, después se quita la columna vieja.
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo que mide
 * @property int|null $hardware_device_monitorized_id Dispositivo medido
 * @property int|null $energy_system_id
 * @property int|null $energy_source_type_id
 * @property string|null $name
 * @property string $role generator | load | storage
 * @property bool|null $is_generator Se conserva hasta migrar del todo a `role` (D70)
 * @property int|null $sensor_position Canal del monitor
 * @property float|null $nominal_voltage
 * @property float|null $voltage_min
 * @property float|null $voltage_max
 * @property float|null $rated_power_w
 * @property float|null $capacity_mah
 * @property float|null $capacity_wh
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read HardwareDevice|null $monitorized
 * @property-read EnergySystem|null $system
 * @property-read EnergySourceType|null $sourceType
 * @property-read Collection<int, HardwarePowerGenerator> $powerGenerators
 * @property-read Collection<int, HardwarePowerLoad> $powerLoads
 *
 * @method static Builder<static>|HardwareEnergy newModelQuery()
 * @method static Builder<static>|HardwareEnergy newQuery()
 * @method static Builder<static>|HardwareEnergy query()
 * @method static Builder<static>|HardwareEnergy forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class HardwareEnergy extends BaseModel
{
    use BelongsToHardwareDevice;
    use SoftDeletes;

    public const ROLE_GENERATOR = 'generator';

    public const ROLE_LOAD = 'load';

    public const ROLE_STORAGE = 'storage';

    /** @var list<string> */
    public const ROLES = [self::ROLE_GENERATOR, self::ROLE_LOAD, self::ROLE_STORAGE];

    /**
     * Márgenes con los que se juzga una tensión cuando el elemento no tiene
     * `voltage_min` / `voltage_max` puestos a mano.
     *
     * Son anchos a propósito: una batería de 12 V nominales va de 10,5 V vacía a
     * 14,8 V en absorción, y un panel de 24 V nominales llega a 40 V en circuito
     * abierto. Estrecharlos sin conocer el montaje marcaría como sospechosas
     * lecturas buenas. Para afinar, se rellenan las dos columnas del elemento.
     */
    private const FACTOR_MIN = 0.5;

    private const FACTOR_MAX = 2.0;

    protected $table = 'hardware_energy';

    protected $fillable = [
        'hardware_device_id', 'hardware_device_monitorized_id',
        'energy_system_id', 'energy_source_type_id',
        'name', 'role', 'is_generator', 'sensor_position',
        'nominal_voltage', 'voltage_min', 'voltage_max',
        'rated_power_w', 'capacity_mah', 'capacity_wh', 'is_active',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_device_monitorized_id' => 'integer',
        'energy_system_id' => 'integer',
        'energy_source_type_id' => 'integer',
        'sensor_position' => 'integer',
        'is_generator' => 'boolean',
        'is_active' => 'boolean',
        'nominal_voltage' => 'float',
        'voltage_min' => 'float',
        'voltage_max' => 'float',
        'rated_power_w' => 'float',
        'capacity_mah' => 'float',
        'capacity_wh' => 'float',
    ];

    // ─────────────────────────── Relaciones ────────────────────────────

    /**
     * Dispositivo monitorizado.
     */
    public function monitorized(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_monitorized_id', 'id');
    }

    /**
     * Instalación a la que pertenece el elemento.
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(EnergySystem::class, 'energy_system_id');
    }

    /**
     * Tipo de fuente: solar, eólica, red…
     */
    public function sourceType(): BelongsTo
    {
        return $this->belongsTo(EnergySourceType::class, 'energy_source_type_id');
    }

    /**
     * Lecturas de consumo **de este elemento**.
     *
     * Antes colgaban del dispositivo (`hardware_device_id` → `hardware_device_id`),
     * con lo que un monitor de cuatro canales devolvía las cuatro corrientes
     * mezcladas para cualquiera de sus elementos.
     */
    public function powerLoads(): HasMany
    {
        return $this->hasMany(HardwarePowerLoad::class, 'hardware_energy_id');
    }

    /**
     * Lecturas de generación de este elemento.
     */
    public function powerGenerators(): HasMany
    {
        return $this->hasMany(HardwarePowerGenerator::class, 'hardware_energy_id');
    }

    /**
     * Lecturas del controlador solar, si el elemento es uno.
     */
    public function solarReadings(): HasMany
    {
        return $this->hasMany(HardwarePowerGeneratorSolar::class, 'hardware_energy_id');
    }

    // ───────────────────────────── Scopes ──────────────────────────────

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeGenerators(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_GENERATOR);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeLoads(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_LOAD);
    }

    /**
     * Filtra por el slug del tipo de fuente: `?source=solar`.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfSource(Builder $query, string $slug): Builder
    {
        return $query->whereHas('sourceType', static fn (Builder $q) => $q->where('slug', $slug));
    }

    /**
     * Filtra por el slug de la instalación: `?system=casa`.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfSystem(Builder $query, string $slug): Builder
    {
        return $query->whereHas('system', static fn (Builder $q) => $q->where('slug', $slug));
    }

    /**
     * Elementos de un usuario, mirando el dueño de la instalación.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('system', static fn (Builder $q) => $q->where('user_id', $userId));
    }

    // ──────────────────────────── Cálculos ─────────────────────────────

    public function isGenerator(): bool
    {
        return $this->role === self::ROLE_GENERATOR;
    }

    /**
     * ¿Es creíble esta tensión para este elemento?
     *
     * Se usan `voltage_min` / `voltage_max` si están puestos; si no, un margen
     * alrededor de la nominal. Si el elemento no tiene ni nominal ni márgenes no
     * hay con qué juzgar, y entonces cualquier tensión positiva pasa: inventarse
     * un criterio marcaría como sospechosas lecturas correctas.
     */
    public function voltageIsPlausible(?float $voltage): bool
    {
        if ($voltage === null || ! is_finite($voltage) || $voltage <= 0.0) {
            return false;
        }

        $min = $this->voltage_min ?? ($this->nominal_voltage !== null ? $this->nominal_voltage * self::FACTOR_MIN : null);
        $max = $this->voltage_max ?? ($this->nominal_voltage !== null ? $this->nominal_voltage * self::FACTOR_MAX : null);

        if ($min !== null && $voltage < $min) {
            return false;
        }

        return ! ($max !== null && $voltage > $max);
    }

    /**
     * Decide con qué tensión se calcula: la medida si es plausible, y si no la
     * nominal del elemento.
     *
     * Devuelve también de dónde salió, que es lo que va a `voltage_source`: un
     * vatio calculado con la tensión nominal y otro con la medida no valen lo
     * mismo, y mezclarlos sin saberlo estropea las sumas.
     *
     * @return array{0: float|null, 1: string}
     */
    public function resolveVoltage(?float $measure): array
    {
        if ($this->voltageIsPlausible($measure)) {
            return [$measure, 'measured'];
        }

        if ($this->nominal_voltage !== null && $this->nominal_voltage > 0.0) {
            return [$this->nominal_voltage, 'nominal'];
        }

        // Ni medida creíble ni nominal. No se inventa un 0: eso convertiría «no
        // tengo dato» en una medición de cero vatios que baja todas las medias.
        return [null, 'measured'];
    }

    /**
     * W = V · A. Potencia media del periodo, no instantánea.
     */
    public function computePower(?float $amperage, ?float $voltage): ?float
    {
        if ($amperage === null || $voltage === null) {
            return null;
        }

        return $voltage * $amperage;
    }

    /**
     * Ah = A · s / 3600.
     */
    public function computeAmpHours(?float $amperage, ?int $seconds): ?float
    {
        if ($amperage === null || $seconds === null || $seconds <= 0) {
            return null;
        }

        return $amperage * $seconds / 3600;
    }

    /**
     * Wh = V · A · s / 3600.
     */
    public function computeWattHours(?float $amperage, ?float $voltage, ?int $seconds): ?float
    {
        if ($voltage === null) {
            return null;
        }

        $amperiosHora = $this->computeAmpHours($amperage, $seconds);

        return $amperiosHora === null ? null : $amperiosHora * $voltage;
    }
}
