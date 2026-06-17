<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de radiación ultravioleta tipo A (UVA).
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value
 * @property string|null $created_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUva whereValue($value)
 *
 * @mixin \Eloquent
 */
class MeteorologyUva extends BaseModel
{
    protected $table = 'meteorology_uva';

    public $timestamps = false;

    protected $fillable = ['user_id', 'hardware_device_id', 'value', 'created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hardwareDevice(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class);
    }
}
