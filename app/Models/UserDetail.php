<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Detalles ampliados del perfil de usuario.
 *
 * @property int $id
 * @property int $user_id Relación con el usuario
 * @property string|null $profession Profesión del usuario.
 * @property string|null $web Web personal del usuario.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereProfession($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail whereWeb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDetail withoutTrashed()
 *
 * @mixin \Eloquent
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
