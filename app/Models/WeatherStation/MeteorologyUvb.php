<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de radiación ultravioleta tipo B (UVB).
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value
 * @property string|null $created_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvb forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class MeteorologyUvb extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $table = 'meteorology_uvb';

    public $timestamps = false;

    protected $fillable = ['user_id', 'hardware_device_id', 'value', 'created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
