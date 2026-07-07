<?php

declare(strict_types=1);

namespace App\Models\Hardware;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Support\Carbon;

/**
 * Class SolarCharge
 *
 * Representa un tipo de hardware concreto más complejo, un cargador solar
 * que tendrá tanto consumo, batería y generación de energía.
 *
 * @property int $id
 * @property int $hardware_device_id
 * @property string|null $date
 * @property string|null $read_at
 * @property string|null $hardware
 * @property string|null $version
 * @property string|null $serial_number
 * @property float|null $battery_voltage
 * @property float|null $battery_current
 * @property float|null $battery_power
 * @property float|null $battery_soc
 * @property float|null $pv_voltage
 * @property float|null $pv_current
 * @property float|null $pv_power
 * @property float|null $load_voltage
 * @property float|null $load_current
 * @property float|null $load_power
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SolarCharge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SolarCharge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SolarCharge query()
 *
 * @mixin \Eloquent
 */
class SolarCharge extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $fillable = [
        'hardware_device_id',
        'date',
        'read_at',
        'hardware',
        'version',
        'serial_number',
        'battery_voltage',
        'battery_current',
        'battery_power',
        'battery_soc',
        'pv_voltage',
        'pv_current',
        'pv_power',
        'load_voltage',
        'load_current',
        'load_power',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'hardware_device_id' => 'integer',
        'battery_voltage' => 'float',
        'battery_current' => 'float',
        'battery_power' => 'float',
        'battery_soc' => 'float',
        'pv_voltage' => 'float',
        'pv_current' => 'float',
        'pv_power' => 'float',
        'load_voltage' => 'float',
        'load_current' => 'float',
        'load_power' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
