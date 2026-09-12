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
 * Lectura instantánea o de intervalo de energía (D115, Fase 3).
 *
 * Unifica las tablas de lecturas de generadores, consumos y baterías solares.
 * La marca temporal es el propio `created_at` del servidor.
 *
 * @property int $id
 * @property int $hardware_device_id Dispositivo que mide
 * @property int|null $hardware_energy_id Elemento medido (canal/rol)
 * @property float|null $voltage Tensión del periodo (V) — crudo
 * @property float|null $amperage Corriente media del periodo (A) — crudo
 * @property float|null $power Potencia media del periodo (W)
 * @property int|null $delta_seconds Segundos que cubre la media (duración de la muestra)
 * @property float|null $energy_wh Vatios-hora calculados para este intervalo (W·s/3600)
 * @property float|null $energy_ah Amperios-hora calculados para este intervalo (A·s/3600)
 * @property string $energy_source device | derived
 * @property string $voltage_source measured | nominal
 * @property float|null $battery_voltage Tensión de la batería si se monitoriza (V)
 * @property int|null $battery_percentage Estado de carga de la batería (0-100 %)
 * @property float|null $temperature Temperatura (°C)
 * @property int|null $fan Estado o velocidad del ventilador
 * @property int|null $charging_status Código del modo de carga
 * @property string|null $charging_status_label Etiqueta textual del modo de carga
 * @property bool|null $light_status Detección de iluminación/luz solar
 * @property int|null $light_brightness Intensidad de luz (0-100 %)
 * @property bool $is_suspicious true si la lectura es anómala (excluida de agregados)
 * @property string|null $suspicious_reason Motivo por el que se marcó sospechosa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice $hardwareDevice
 * @property-read HardwareEnergy|null $hardwareEnergy
 * @property-read HardwareEnergy|null $energy
 *
 * @method static Builder<static>|HardwareEnergyReading newModelQuery()
 * @method static Builder<static>|HardwareEnergyReading newQuery()
 * @method static Builder<static>|HardwareEnergyReading query()
 * @method static Builder<static>|HardwareEnergyReading forDevice(int $deviceId)
 * @method static Builder<static>|HardwareEnergyReading forElement(int $elementId)
 * @method static Builder<static>|HardwareEnergyReading reliable()
 * @method static Builder<static>|HardwareEnergyReading suspicious(bool $suspicious = true)
 * @method static Builder<static>|HardwareEnergyReading betweenDates(?string $from, ?string $to)
 *
 * @mixin \Eloquent
 */
class HardwareEnergyReading extends BaseModel
{
    use BelongsToHardwareDevice;
    use HasFactory;

    protected $table = 'hardware_energy_readings';

    protected $fillable = [
        'hardware_device_id',
        'hardware_energy_id',
        'voltage',
        'amperage',
        'power',
        'delta_seconds',
        'energy_wh',
        'energy_ah',
        'energy_source',
        'voltage_source',
        'battery_voltage',
        'battery_percentage',
        'temperature',
        'fan',
        'charging_status',
        'charging_status_label',
        'light_status',
        'light_brightness',
        'is_suspicious',
        'suspicious_reason',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'hardware_energy_id' => 'integer',
        'voltage' => 'float',
        'amperage' => 'float',
        'power' => 'float',
        'delta_seconds' => 'integer',
        'energy_wh' => 'float',
        'energy_ah' => 'float',
        'battery_voltage' => 'float',
        'battery_percentage' => 'integer',
        'temperature' => 'float',
        'fan' => 'integer',
        'charging_status' => 'integer',
        'light_status' => 'boolean',
        'light_brightness' => 'integer',
        'is_suspicious' => 'boolean',
    ];

    // ─────────────────────────── Relaciones ────────────────────────────

    /**
     * Elemento energético (canal/rol) al que corresponde la lectura.
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
     * Filtra lecturas por elemento medido.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForElement(Builder $query, int $elementId): Builder
    {
        return $query->where('hardware_energy_id', $elementId);
    }

    /**
     * Lecturas fiables (no marcadas como sospechosas).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeReliable(Builder $query): Builder
    {
        return $query->where('is_suspicious', false);
    }

    /**
     * Lecturas sospechosas o anómalas.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSuspicious(Builder $query, bool $suspicious = true): Builder
    {
        return $query->where('is_suspicious', $suspicious);
    }

    /**
     * Rango temporal por created_at (cuándo se registró en servidor).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, static fn (Builder $q, string $d) => $q->where('created_at', '>=', $d))
            ->when($to, static fn (Builder $q, string $h) => $q->where('created_at', '<=', $h));
    }

    // ───────────────────────────── Métodos ─────────────────────────────

    /**
     * Marca la lectura como sospechosa acumulando motivos.
     */
    public function markSuspicious(string $reason): static
    {
        $previous = ($this->suspicious_reason !== null && $this->suspicious_reason !== '')
            ? $this->suspicious_reason.'; '
            : '';

        $this->is_suspicious = true;
        $this->suspicious_reason = mb_substr($previous.$reason, 0, 255);

        return $this;
    }
}
