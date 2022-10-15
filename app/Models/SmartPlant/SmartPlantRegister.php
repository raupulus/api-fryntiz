<?php

namespace App\Models\SmartPlant;

use App\Models\BaseModels\BaseModel;

/**
 * Class SmartPlantRegister
 *
 * Representa los registros de sensores para las lecturas tomadas a las
 * plantas asociadas en cada momento.
 *
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
}
