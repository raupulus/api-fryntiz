<?php

namespace App\Models\SmartPlant;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SmartPlanRegister
 *
 * Representa los registros de sensores para las lecturas tomadas a las
 * plantas asociadas en cada momento.
 *
 * @package App\Models\SmartPlant
 */
class SmartPlanRegister extends Model
{
    protected $table = 'smartplant_registers';

    protected $fillable = [
        'plant_id',
        'uv',
        'temperature',
        'humidity',
        'soil_humidity',
        'full_water_tank',
        'waterpump_enabled',
        'vaporizer_enabled',
    ];

    public function setUpdatedAt($value)
    {
        // Desactivo el updated_at
    }
}
