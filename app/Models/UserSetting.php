<?php

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Preferencias de notificación del usuario.
 *
 * @property int $id
 * @property int $user_id Relación con el usuario
 * @property bool|null $send_email Indica si permite el envío de emails con información no prioritaria
 * @property bool|null $send_notification Indica si quiere notificaciones.
 * @property bool|null $send_notification_push Indica si permite notificaciones push
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendNotification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendNotificationPush($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting withoutTrashed()
 * @mixin \Eloquent
 */
class UserSetting extends BaseModel
{
    use SoftDeletes;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id', 'send_email', 'send_notification', 'send_notification_push',
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'send_notification' => 'boolean',
        'send_notification_push' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
