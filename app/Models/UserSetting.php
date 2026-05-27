<?php

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Preferencias de notificación del usuario.
 *
 * @property int $user_id
 * @property bool $send_email
 * @property bool $send_notification
 * @property bool $send_notification_push
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
