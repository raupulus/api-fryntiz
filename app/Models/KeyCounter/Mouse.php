<?php

declare(strict_types=1);

namespace App\Models\KeyCounter;

use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Class Mouse
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property string $start_at Momento de iniciar la racha
 * @property string $end_at Momento del final de la racha
 * @property int $duration Duración en Segundos de la racha
 * @property int $clicks_left Cantidad de clicks derecho
 * @property int $clicks_right Cantidad de clicks izquierdo
 * @property int $clicks_middle Cantidad de clicks centrales
 * @property int $total_clicks Cantidad de clicks total de la racha
 * @property numeric $clicks_average Cantidad de cliks medio de la racha
 * @property int $weekday Día de la semana (0 es domingo)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice|null $device
 * @property-read HardwareDevice|null $hardware
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereClicksAverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereClicksLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereClicksMiddle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereClicksRight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereTotalClicks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse whereWeekday($value)
 *
 * @property-read HardwareDevice|null $hardwareDevice
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mouse forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class Mouse extends Keyboard
{
    protected $table = 'keycounter_mouse';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'start_at',
        'end_at',
        'duration',
        'clicks_left',
        'clicks_right',
        'clicks_middle',
        'total_clicks',
        'clicks_average',
        'weekday',
    ];

    /**
     * Devuelve todos los elementos del modelo.
     */
    public static function all($columns = ['*'])
    {
        $query = parent::all();
        $query->where('start_at', '!=', null)
            ->where('end_at', '!=', null)
            ->where('pulsations', '!=', null)
            ->where('total_clicks', '>', 0)
            ->where('clicks_average', '>', 0)
            ->where('weekday', '!=', null)
            ->where('created_at', '!=', null)
            ->sortByDesc('created_at');

        return $query;
    }
}
