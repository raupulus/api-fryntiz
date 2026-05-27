<?php

namespace App\Models\SmartPlant;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa los registros de sensores para las lecturas tomadas a las
 * plantas asociadas en cada momento.
 *
 * @property int $plant_id
 * @property int|null $hardware_device_id
 * @property int|null $soil_humidity
 */
class SmartPlantRegister extends BaseModel
{
    protected $table = 'smartplant_registers';

    protected $fillable = [
        'plant_id',
        'hardware_device_id',
        'uv',
        'pressure',
        'temperature',
        'humidity',
        'soil_humidity',
        'soil_humidity_raw',
        'full_water_tank',
        'waterpump_enabled',
        'vaporizer_enabled',
    ];

    public function setUpdatedAt($value)
    {
        // Desactivo el updated_at
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(SmartPlantPlant::class, 'plant_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class, 'hardware_device_id');
    }
}
