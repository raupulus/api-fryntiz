<?php

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class HardwarePowerLoadHistorical
 */
class HardwarePowerLoadHistorical extends BaseModel
{
    use HasFactory;

    protected $table = 'hardware_power_loads_historical';
}
