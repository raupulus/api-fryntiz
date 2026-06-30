<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Platform
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo que se usa como monitor
 * @property int|null $hardware_device_monitorized_id Dispositivo que está siendo monitorizado
 * @property bool|null $is_generator Indica si el dispositivo monitorizado es un generador de energía o un consumidor
 * @property int|null $sensor_position Posición del sensor en el dispositivo monitorizado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read HardwareDevice|null $monitorized
 * @property-read Collection<int, HardwarePowerGenerator> $powerGenerators
 * @property-read int|null $power_generators_count
 * @property-read Collection<int, HardwarePowerLoad> $powerLoads
 * @property-read int|null $power_loads_count
 *
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
 *
 * @mixin \Eloquent
 */
class HardwareEnergy extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $table = 'hardware_energy';

    protected $fillable = ['hardware_device_id', 'hardware_device_monitorized_id', 'is_generator', 'sensor_position'];

    /**
     * Dispositivo monitorizado.
     */
    public function monitorized(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_monitorized_id', 'id');
    }

    /**
     * Cargas de energía del dispositivo monitorizado.
     */
    public function powerLoads(): HasMany
    {
        return $this->hasMany(HardwarePowerLoad::class, 'hardware_device_id', 'hardware_device_id');
    }

    /**
     * Generadores de energía del dispositivo monitorizado.
     */
    public function powerGenerators(): HasMany
    {
        return $this->hasMany(HardwarePowerGenerator::class, 'hardware_device_id', 'hardware_device_id');
    }
}
