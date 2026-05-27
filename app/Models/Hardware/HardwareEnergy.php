<?php

namespace App\Models\Hardware;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Platform
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
    public function hardware()
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id', 'id');
    }

    /**
     * Dispositivo monitorizado.
     *
     * @return BelongsTo
     */
    public function monitorized()
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_monitorized_id', 'id');
    }

    /**
     * Alias para Filament Resources.
     *
     * @return BelongsTo
     */
    public function device()
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id', 'id');
    }

    /**
     * Cargas de energía del dispositivo monitorizado.
     *
     * @return HasMany
     */
    public function powerLoads()
    {
        return $this->hasMany(HardwarePowerLoad::class, 'hardware_device_id', 'hardware_device_id');
    }

    /**
     * Generadores de energía del dispositivo monitorizado.
     *
     * @return HasMany
     */
    public function powerGenerators()
    {
        return $this->hasMany(HardwarePowerGenerator::class, 'hardware_device_id', 'hardware_device_id');
    }
}
