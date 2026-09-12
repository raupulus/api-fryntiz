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
 * **Una fila es un papel de un dispositivo, no el dispositivo.** El mismo
 * aparato puede tener las tres: lo que produce el panel (`generator`), lo que
 * gasta la carga (`load`) y lo que hay en la batería (`battery`). Así se agrupa
 * y se suma por papel sin mirar de qué aparato viene cada lectura.
 *
 * De lo único que se puede dar por hecho que no cambia entre las filas de un
 * mismo monitor es **el propio monitor**: el dispositivo medido, la instalación,
 * la fuente y el `is_active` son de cada canal. Una Raspberry con un INA puede
 * llevar la batería de 12 V a un ventilador, la de litio a una lámpara y el
 * cargador de red a un microcontrolador: tres canales, tres cosas medidas y tres
 * fuentes distintas.
 *
 * `is_generator` **ya no existe** (2026-09-07). Duplicaba a `role` y encima no
 * sabía decir «batería»: la dejaba en `false`, indistinguible de una carga.
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo que mide
 * @property int|null $hardware_device_monitorized_id Dispositivo medido
 * @property int|null $energy_source_type_id
 * @property string $role generator | load | battery
 * @property int $sensor_position Canal del monitor. 0 si sólo tiene uno
 * @property float|null $nominal_voltage
 * @property float|null $voltage_min
 * @property float|null $voltage_max
 * @property float|null $rated_power_w
 * @property float|null $capacity_ah Capacidad nominal de batería en Ah (resolución 1 mAh)
 * @property bool $auto_calculate_history true si el cron nocturno consolida/recalcula históricos
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read float|null $capacity_wh Capacidad calculada dinámicamente en Wh (nominal_voltage * capacity_ah)
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read HardwareDevice|null $monitorized
 * @property-read EnergySourceType|null $sourceType
 * @property-read Collection<int, HardwareEnergyReading> $readings
 * @property-read Collection<int, HardwareEnergyToday> $today
 * @property-read Collection<int, HardwareEnergyHistorical> $historical
 * @property-read Collection<int, HardwarePowerGenerator> $powerGenerators
 * @property-read Collection<int, HardwarePowerLoad> $powerLoads
 *
 * @method static Builder<static>|HardwareEnergy newModelQuery()
 * @method static Builder<static>|HardwareEnergy newQuery()
 * @method static Builder<static>|HardwareEnergy query()
 * @method static Builder<static>|HardwareEnergy forDevice(int $deviceId)
 * @method static Builder<static>|HardwareEnergy generators()
 * @method static Builder<static>|HardwareEnergy loads()
 * @method static Builder<static>|HardwareEnergy batteries()
 *
 * @mixin \Eloquent
 */
class HardwareEnergy extends BaseModel
{
    use BelongsToHardwareDevice;
    use SoftDeletes;

    public const ROLE_GENERATOR = 'generator';

    public const ROLE_LOAD = 'load';

    /**
     * Lo que almacena: el banco de baterías.
     *
     * Se llamaba `storage` y la etiqueta del panel ya decía «Batería»: el valor
     * guardado decía una cosa y la interfaz otra.
     */
    public const ROLE_BATTERY = 'battery';

    /** @var list<string> */
    public const ROLES = [self::ROLE_GENERATOR, self::ROLE_LOAD, self::ROLE_BATTERY];

    /**
     * Cuántas filas admite cada papel por dispositivo monitor.
     *
     * Un montaje tiene un campo solar y un banco de baterías, pero un monitor
     * mide **tantas cargas como canales tenga**: una Raspberry con un INA puede
     * llevar la batería de 12 V a un ventilador, la de litio a una lámpara y el
     * cargador de red a un microcontrolador.
     *
     * `null` = sin límite.
     *
     * @var array<string, int|null>
     */
    public const LIMIT_PER_ROLE = [
        self::ROLE_GENERATOR => 1,
        self::ROLE_BATTERY => 1,
        self::ROLE_LOAD => null,
    ];

    /**
     * Cómo se llama cada papel en la interfaz.
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        self::ROLE_GENERATOR => 'Generador',
        self::ROLE_LOAD => 'Consumo',
        self::ROLE_BATTERY => 'Batería',
    ];

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
        'energy_source_type_id',
        'role', 'sensor_position',
        'nominal_voltage', 'voltage_min', 'voltage_max',
        'rated_power_w', 'capacity_ah', 'auto_calculate_history', 'is_active',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_device_monitorized_id' => 'integer',
        'energy_source_type_id' => 'integer',
        'sensor_position' => 'integer',
        'is_active' => 'boolean',
        'nominal_voltage' => 'float',
        'voltage_min' => 'float',
        'voltage_max' => 'float',
        'rated_power_w' => 'float',
        'capacity_ah' => 'float',
        'auto_calculate_history' => 'boolean',
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
     * Los papeles del mismo medidor, éste incluido.
     *
     * Un aparato puede tener los tres —lo que produce, lo que gasta y lo que
     * almacena— y son filas distintas. Sin esta relación, desde la ficha de uno
     * no había forma de llegar a los otros: había que volver al listado,
     * buscarlo y entrar, y para el que **todavía no existe** directamente no
     * había camino.
     *
     * @return HasMany<self, $this>
     */
    public function rolesOnSameMeter(): HasMany
    {
        return $this->hasMany(self::class, 'hardware_device_id', 'hardware_device_id');
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

    /**
     * Lecturas unificadas de energía de este elemento.
     *
     * @return HasMany<HardwareEnergyReading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(HardwareEnergyReading::class, 'hardware_energy_id');
    }

    /**
     * Resúmenes diarios de energía de este elemento.
     *
     * @return HasMany<HardwareEnergyToday, $this>
     */
    public function today(): HasMany
    {
        return $this->hasMany(HardwareEnergyToday::class, 'hardware_energy_id');
    }

    /**
     * Acumulados históricos de energía por sesión de este elemento.
     *
     * @return HasMany<HardwareEnergyHistorical, $this>
     */
    public function historical(): HasMany
    {
        return $this->hasMany(HardwareEnergyHistorical::class, 'hardware_energy_id');
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
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeBatteries(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_BATTERY);
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
     * Cómo se llama este elemento cuando hay que nombrarlo.
     *
     * Sustituye a la columna `name`, que era un campo más que rellenar a mano
     * para escribir lo que ya se sabe: el elemento es «tal aparato haciendo tal
     * papel», y las dos cosas están en la fila. Sale en los avisos de la API
     * cuando una lectura suya es rara.
     */
    public function getDisplayNameAttribute(): string
    {
        // **Sólo relaciones ya cargadas.** Esto es un accesorio, y se pinta en
        // listados y en los avisos de cada lectura: si tirase de la relación,
        // sería una consulta por fila. El proyecto tiene el lazy loading
        // desactivado, así que además reventaría en vez de ir despacio en
        // silencio.
        //
        // Quien lo necesite con nombre, que cargue `monitorized`. Sin eso sale
        // el id, que sigue identificando la fila.
        $deviceName = 'Elemento #'.$this->id;

        foreach (['monitorized', 'hardwareDevice'] as $relation) {
            if (! $this->relationLoaded($relation)) {
                continue;
            }

            $device = $this->getRelation($relation);

            if ($device instanceof HardwareDevice) {
                $deviceName = $device->display_name;

                break;
            }
        }

        $role = self::ROLE_LABELS[$this->role] ?? $this->role;

        // El canal sólo se nombra cuando el monitor tiene más de uno, que es
        // cuando de verdad hace falta para distinguirlos.
        $channel = $this->sensor_position > 0 ? " · canal {$this->sensor_position}" : '';

        return "{$deviceName} · ".mb_strtolower($role).$channel;
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

    /**
     * Capacidad de almacenamiento nominal en Vatios-hora (Wh).
     *
     * Se calcula dinámicamente a partir de la capacidad en Ah y la tensión
     * nominal (Wh = Ah · V_nom).
     */
    public function getCapacityWhAttribute(): ?float
    {
        if ($this->capacity_ah !== null && $this->nominal_voltage !== null) {
            return round((float) $this->capacity_ah * (float) $this->nominal_voltage, 2);
        }

        return null;
    }
}
