<?php

namespace App\Traits;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait para modelos de sensores/datos que pertenecen a un dispositivo hardware.
 */
trait BelongsToHardwareDevice
{
    /**
     * Relación: pertenece a un dispositivo hardware.
     */
    public function hardwareDevice(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id');
    }

    /**
     * Scope para filtrar por dispositivo.
     */
    public function scopeForDevice($query, int $deviceId)
    {
        return $query->where('hardware_device_id', $deviceId);
    }
}
