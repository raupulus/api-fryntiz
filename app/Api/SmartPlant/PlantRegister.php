<?php

namespace App\SmartPlant;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Plant
 *
 * Representa los registros de sensores para las lecturas tomadas a las
 * plantas asociadas en cada momento.
 *
 * @package App\SmartPlant
 */
class PlantRegister extends Model
{
    protected $table = 'smartbonsai_registers';
}
