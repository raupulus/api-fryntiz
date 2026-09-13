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
 * @property float|null $energy_wh_device_total Último total de Wh que reportó el aparato
 * @property float|null $energy_ah_device_total Último total de Ah que reportó el aparato
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

    /**
     * Dónde se recuerda el último odómetro que reportó el aparato, magnitud a
     * magnitud.
     *
     * Es lo que permite sumar **avances** en vez de sustituir totales, y lo
     * único contra lo que tiene sentido comparar para saber si el aparato se ha
     * reiniciado: comparar su odómetro contra nuestro acumulado es comparar dos
     * cosas que no miden lo mismo.
     *
     * @var array<string, string>
     */
    public const DEVICE_TOTAL_COLUMNS = [
        'energy_wh' => 'energy_wh_device_total',
        'energy_ah' => 'energy_ah_device_total',
    ];

    /**
     * Por debajo de qué fracción del último odómetro se considera que el
     * aparato ha vuelto a contar desde cero.
     *
     * No es `< anterior` a secas porque un registro Modbus leído a medias o una
     * lectura corrupta hacen bajar la cifra sin que nada se haya reiniciado.
     */
    private const RESET_FACTOR = 0.5;

    /**
     * Odómetro por debajo del cual no se juzga nada: con cifras pequeñas, medio
     * vatio-hora de ruido dispara cualquier proporción.
     */
    private const RESET_FLOOR = 50.0;

    protected $table = 'hardware_energy_historical';

    protected $fillable = [
        'hardware_device_id',
        'hardware_energy_id',
        'session_index',
        'days_operating',
        'readings_count',
        'energy_wh_source',
        'energy_ah_source',
        'energy_wh_device_total',
        'energy_ah_device_total',
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
        'energy_wh_device_total' => 'float',
        'energy_ah_device_total' => 'float',
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

        // **El reinicio se detecta comparando el odómetro consigo mismo.**
        //
        // Antes esto comparaba el total que manda el aparato contra el que
        // tenemos acumulado, y son dos números que no miden lo mismo: el
        // nuestro puede venir de sumar años de resúmenes diarios y el suyo de
        // un registro que empezó a contar mucho después. Con esa regla,
        // cualquier aparato cuyo contador vaya por debajo de nuestra suma
        // parecía reiniciado en cuanto abría la boca — y partía el histórico en
        // dos. Pasó en producción el 13/09/2026: la primera subida con el
        // contrato nuevo abrió una sesión 2 en el panel, el consumo y la
        // batería del Rover sin que el controlador se hubiera reiniciado.
        $isReset = $latest !== null && (
            self::odometroReiniciado($reportedWh, $latest->energy_wh_device_total)
            || self::odometroReiniciado($reportedAh, $latest->energy_ah_device_total)
        );

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
     * ¿El aparato ha vuelto a contar desde cero?
     *
     * Sólo se puede responder comparando su odómetro con **el último valor que
     * él mismo reportó**. Mientras no haya uno anterior con el que comparar
     * —la primera vez que manda esta magnitud— no se puede afirmar nada, y no
     * afirmar nada es lo correcto: dar por reiniciado lo que no lo está parte
     * el histórico en dos.
     */
    private static function odometroReiniciado(?float $reportado, ?float $ultimoDelAparato): bool
    {
        if ($reportado === null || $ultimoDelAparato === null) {
            return false;
        }

        return $ultimoDelAparato > self::RESET_FLOOR
            && $reportado < ($ultimoDelAparato * self::RESET_FACTOR);
    }

    /**
     * Acumula una magnitud respetando de dónde sale.
     *
     * ## Con odómetro del aparato: se suma lo que avanza, no lo que marca
     *
     * Un odómetro es un total absoluto, y lo que aporta a nuestro acumulado es
     * **su avance desde la última vez**, no su valor. Guardarlo tal cual —con
     * `max()`, como se hacía— tenía dos formas de salir mal:
     *
     * - Si nuestro total ya era mayor, el acumulado se quedaba congelado. El
     *   Rover lleva 524.497 Wh contados desde 2022 y su registro marca 41.206:
     *   ninguna lectura suya volvería a mover la cifra en años.
     * - Si el aparato se adoptaba de golpe, su odómetro traía dentro energía
     *   que ya estaba contada en nuestro total, y se contaba dos veces.
     *
     * Por eso la **primera** vez que llega el odómetro de una magnitud sólo se
     * anota el punto de partida: no hay forma de saber cuánto de lo que marca
     * ya está en lo que tenemos. Desde ahí se suman avances. Si el aparato se
     * ha reiniciado, todo lo que marca es nuevo y se suma entero —aunque el
     * caso normal es que {@see self::accumulateForElement()} ya haya abierto
     * otra sesión y esta fila empiece de cero—.
     *
     * Si no hay nada acumulado todavía, el odómetro **es** el total: es el
     * aparato recién dado de alta, y su historia entera es la suya.
     *
     * ## Sin odómetro: se suman nuestros deltas
     *
     * Y en cuanto una magnitud ha traído odómetro alguna vez queda fijada como
     * `device` para el resto de la sesión: sus totales son los del aparato y
     * los deltas que calculamos nosotros dejan de sumarse, porque sumarlos
     * encima de un total absoluto lo infla sin vuelta atrás.
     *
     * @param  'energy_wh'|'energy_ah'  $magnitud
     * @param  float|null  $odometro  Total absoluto declarado por el aparato.
     * @param  mixed  $delta  Energía de este intervalo, nuestra o suya.
     */
    private function acumulaMagnitud(string $magnitud, ?float $odometro, mixed $delta): void
    {
        $columnaOrigen = self::SOURCE_COLUMNS[$magnitud];
        $columnaOdometro = self::DEVICE_TOTAL_COLUMNS[$magnitud];

        if ($odometro !== null) {
            $anterior = $this->{$columnaOdometro} !== null ? (float) $this->{$columnaOdometro} : null;

            $this->{$columnaOrigen} = self::SOURCE_DEVICE;

            if ($anterior === null) {
                // Primera vez que vemos su odómetro. Si no hay nada acumulado,
                // su total es el nuestro; si lo hay, sólo se adopta el punto de
                // partida y desde aquí se cuentan avances.
                $this->{$columnaOdometro} = $odometro;

                if ((float) $this->{$magnitud} <= 0.0) {
                    $this->{$magnitud} = $odometro;
                }

                return;
            }

            if ($odometro < $anterior) {
                // Retrocede sin llegar a reiniciarse: un registro leído a
                // medias, una lectura corrupta. Ni suma ni mueve la referencia
                // hacia atrás —moverla convertiría la siguiente lectura buena
                // en un avance enorme—. Un reinicio de verdad no pasa por aquí:
                // lo detecta {@see self::accumulateForElement()} y abre otra
                // sesión, que empieza sin odómetro anterior.
                return;
            }

            $this->{$columnaOdometro} = $odometro;
            $this->{$magnitud} = (float) $this->{$magnitud} + ($odometro - $anterior);

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
