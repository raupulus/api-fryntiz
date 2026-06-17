<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;

/**
 * Class SolarCharge
 *
 * Representa un tipo de hardware concreto más complejo, un cargador solar
 * que tendrá tanto consumo, batería y generación de energía.
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SolarCharge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SolarCharge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SolarCharge query()
 *
 * @mixin \Eloquent
 */
class SolarCharge extends BaseModel {}
