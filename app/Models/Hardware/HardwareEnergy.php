<?php

namespace App\Models\Hardware;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Platform
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo que se usa como monitor
 * @property int|null $hardware_device_monitorized_id Dispositivo que está siendo monitorizado
 * @property bool|null $is_generator Indica si el dispositivo monitorizado es un generador de energía o un consumidor
 * @property int|null $sensor_position Posición del sensor en el dispositivo monitorizado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Hardware\HardwareDevice|null $device
 * @property-read \App\Models\Hardware\HardwareDevice|null $hardware
 * @property-read \App\Models\Hardware\HardwareDevice|null $monitorized
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hardware\HardwarePowerGenerator> $powerGenerators
 * @property-read int|null $power_generators_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hardware\HardwarePowerLoad> $powerLoads
 * @property-read int|null $power_loads_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereHardwareDeviceMonitorizedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereIsGenerator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereSensorPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HardwareEnergy whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class HardwareEnergy extends Model
{
    protected $table = 'hardware_energy';

    protected $fillable = ['hardware_device_id', 'hardware_device_monitorized_id', 'is_generator', 'sensor_position'];

    /**
     * Relación con el dispositivo que monitoriza la energía.
     *
     * @return BelongsTo
     */
    public function hardware(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id', 'id');
    }

    /**
     * Dispositivo monitorizado.
     *
     * @return BelongsTo
     */
    public function monitorized(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_monitorized_id', 'id');
    }

    /**
     * Alias para Filament Resources.
     *
     * @return BelongsTo
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id', 'id');
    }

    /**
     * Cargas de energía del dispositivo monitorizado.
     *
     * @return HasMany
     */
    public function powerLoads(): HasMany
    {
        return $this->hasMany(HardwarePowerLoad::class, 'hardware_device_id', 'hardware_device_id');
    }

    /**
     * Generadores de energía del dispositivo monitorizado.
     *
     * @return HasMany
     */
    public function powerGenerators(): HasMany
    {
        return $this->hasMany(HardwarePowerGenerator::class, 'hardware_device_id', 'hardware_device_id');
    }
}
