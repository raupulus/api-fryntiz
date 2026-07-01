<?php

declare(strict_types=1);

namespace App\Models\KeyCounter;

use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Class Keyboard
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property string $start_at Momento de iniciar la racha
 * @property string $end_at Momento del final de la racha
 * @property int $duration Duración en Segundos de la racha
 * @property int $pulsations Cantidad de pulsaciones total de la racha
 * @property int $pulsations_special_keys Cantidad de pulsaciones para teclas especiales total de la racha
 * @property numeric $pulsation_average Velocidad media de pulsaciones para la racha
 * @property int $score Puntuación conseguida en esta racha
 * @property int $weekday Día de la semana (0 es domingo)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HardwareDevice|null $device
 * @property-read HardwareDevice|null $hardware
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard wherePulsationAverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard wherePulsations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard wherePulsationsSpecialKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard whereWeekday($value)
 *
 * @property-read HardwareDevice|null $hardwareDevice
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Keyboard forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class Keyboard extends BaseKeyCounter
{
    protected $table = 'keycounter_keyboard';

    protected $fillable = [
        'user_id',
        'hardware_device_id',
        'start_at',
        'end_at',
        'duration',
        'pulsations',
        'pulsations_special_keys',
        'pulsation_average',
        'score',
        'weekday',
    ];
}
