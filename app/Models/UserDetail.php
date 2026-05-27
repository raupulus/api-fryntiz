<?php

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Detalles ampliados del perfil de usuario.
 *
 * @property int $user_id
 * @property string|null $profession
 * @property string|null $web
 */
class UserDetail extends BaseModel
{
    use SoftDeletes;

    protected $table = 'user_details';

    protected $fillable = ['user_id', 'profession', 'web'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
