<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro del índice UV.
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric $value
 * @property string|null $created_at
 * @property-read HardwareDevice|null $hardwareDevice
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyUvIndex forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class MeteorologyUvIndex extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $table = 'meteorology_uv_index';

    public $timestamps = false;

    protected $fillable = ['user_id', 'hardware_device_id', 'value', 'created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
