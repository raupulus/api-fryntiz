<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
 * @property string $energy_wh_source device = odómetro del aparato | derived = suma de nuestras lecturas
 * @property string $energy_ah_source device = odómetro del aparato | derived = suma de nuestras lecturas
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

    /**
     * Esta magnitud la lleva el odómetro del aparato.
     */
    public const SOURCE_DEVICE = 'device';

    /**
     * Esta magnitud sale de sumar nuestras propias lecturas.
     */
    public const SOURCE_DERIVED = 'derived';

    /**
     * Las dos magnitudes acumuladas y la columna que dice de dónde sale cada una.
     *
     * El origen es **por magnitud** porque un aparato puede traer odómetro de
     * una y no de la otra: el Renogy Rover manda vatios-hora y amperios-hora de
     * generación y de consumo, pero de la batería sólo manda amperios-hora.
     *
     * @var array<string, string>
     */
    public const SOURCE_COLUMNS = [
        'energy_wh' => 'energy_wh_source',
        'energy_ah' => 'energy_ah_source',
    ];

    protected $table = 'hardware_energy_historical';

    protected $fillable = [
        'hardware_device_id',
        'hardware_energy_id',
        'session_index',
        'days_operating',
        'readings_count',
        'energy_wh_source',
        'energy_ah_source',
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
        'energy_wh_source' => 'string',
        'energy_ah_source' => 'string',
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
        // La sesión abierta se busca por elemento, igual que el índice único;
        // con el dispositivo por medio una fila mal atribuida quedaba invisible
        // y se abría una sesión nueva encima de la que ya existía.
        /** @var static|null $latest */
        $latest = static::query()
            ->when(
                $elementId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $elementId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
                    ->where('hardware_device_id', $deviceId)
            )
            ->orderByDesc('session_index')
            ->lockForUpdate()
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
            $record = self::lockSession($deviceId, $elementId, 1);
        } elseif ($isReset) {
            $record = self::lockSession($deviceId, $elementId, (int) $latest->session_index + 1);
        } else {
            $record = $latest;
            $record->hardware_device_id = $deviceId;
        }

        // Actualizar extremos
        $record->updateExtremes($data);

        // **Lo que el aparato manda se guarda tal cual; lo que no manda se
        // calcula de las lecturas. Magnitud a magnitud.**
        //
        // Antes estas columnas eran dos cosas a la vez: odómetro absoluto en las
        // lecturas que traían `historical_*` y acumulador incremental en las que
        // no. Bastaba con que un aparato con odómetro se dejara el campo en una
        // lectura para sumarle un delta encima de su total, y como el valor se
        // guarda con `max()`, ese inflado no se deshacía nunca.
        //
        // El primer arreglo usaba **una sola** marca para las dos magnitudes, y
        // eso rompía al Renogy Rover: manda vatios-hora y amperios-hora de
        // generación y de consumo, pero de la batería sólo manda amperios-hora
        // —no hay registro Modbus de vatios-hora de batería—. Al marcar la
        // sesión entera como `device`, los Wh de la batería se quedaban
        // clavados a 0 para siempre mientras el resumen del día sí los
        // calculaba: dos tablas diciendo cosas distintas de lo mismo.
        //
        // No se mira `auto_calculate_history` porque es una casilla que se
        // puede quedar mal puesta —su `default(true)` marcó como
        // auto-calculados a los controladores que traen odómetro propio—. Esto
        // es un hecho observado de la serie.
        $record->acumulaMagnitud('energy_wh', $reportedWh, $data['energy_wh'] ?? null);
        $record->acumulaMagnitud('energy_ah', $reportedAh, $data['energy_ah'] ?? null);

        // Días de operación si vienen dados por el dispositivo
        if (isset($data['days_operating'])) {
            $record->days_operating = max((int) $record->days_operating, (int) $data['days_operating']);
        } elseif (isset($data['total_operating_days'])) {
            $record->days_operating = max((int) $record->days_operating, (int) $data['total_operating_days']);
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
     * Acumula una magnitud respetando de dónde sale.
     *
     * En cuanto llega un odómetro de esta magnitud, la magnitud queda fijada
     * como `device` para el resto de la sesión: sus totales son los del aparato
     * y los deltas que calculamos nosotros dejan de sumarse, porque sumarlos
     * encima de un total absoluto lo infla sin vuelta atrás. Mientras no llegue
     * ninguno, se van sumando los deltas de cada lectura.
     *
     * El `max()` es lo que impide que un acumulado baje: un odómetro que
     * retrocede sin llegar a la mitad —una lectura corrupta, un registro leído
     * a medias— no borra lo que ya había. Un reinicio de verdad no pasa por
     * aquí: lo detecta {@see self::accumulateForElement()} y abre otra sesión.
     *
     * @param  'energy_wh'|'energy_ah'  $magnitud
     * @param  float|null  $odometro  Total absoluto declarado por el aparato.
     * @param  mixed  $delta  Energía de este intervalo, nuestra o suya.
     */
    private function acumulaMagnitud(string $magnitud, ?float $odometro, mixed $delta): void
    {
        $columnaOrigen = self::SOURCE_COLUMNS[$magnitud];

        if ($odometro !== null) {
            $this->{$columnaOrigen} = self::SOURCE_DEVICE;
            $this->{$magnitud} = max((float) $this->{$magnitud}, $odometro);

            return;
        }

        if ($this->{$columnaOrigen} === self::SOURCE_DEVICE || $delta === null || $delta === '') {
            return;
        }

        $this->{$magnitud} = (float) $this->{$magnitud} + (float) $delta;
    }

    /**
     * La fila de una sesión concreta, bloqueada para escritura, creándola si no
     * existe.
     *
     * Mismo motivo que en {@see HardwareEnergyToday::lockDaily()}: dos subidas
     * solapadas abrían la misma sesión dos veces y la segunda chocaba contra
     * `hardware_energy_historical_energy_session_unique`. La creación va en una
     * transacción anidada —un SAVEPOINT— para que perderla no aborte la
     * transacción de fuera.
     *
     * La clave de búsqueda es la del índice único —elemento y sesión—, no
     * elemento, sesión y dispositivo: ver {@see HardwareEnergyToday::lockDaily()}
     * para por qué incluir el dispositivo devolvía un 500.
     */
    private static function lockSession(int $deviceId, ?int $elementId, int $sessionIndex): static
    {
        $buscar = static fn (): ?static => static::query()
            ->when(
                $elementId !== null,
                static fn (Builder $q) => $q->where('hardware_energy_id', $elementId),
                static fn (Builder $q) => $q->whereNull('hardware_energy_id')
                    ->where('hardware_device_id', $deviceId)
            )
            ->where('session_index', $sessionIndex)
            ->lockForUpdate()
            ->first();

        if ($record = $buscar()) {
            $record->hardware_device_id = $deviceId;

            return $record;
        }

        try {
            return DB::transaction(static fn (): static => static::query()->create([
                'hardware_device_id' => $deviceId,
                'hardware_energy_id' => $elementId,
                'session_index' => $sessionIndex,
                'days_operating' => 1,
                'readings_count' => 0,
                'energy_wh' => 0.0,
                'energy_ah' => 0.0,
            ]));
        } catch (UniqueConstraintViolationException $e) {
            return $buscar() ?? throw $e;
        }
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
