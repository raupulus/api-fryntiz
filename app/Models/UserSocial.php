<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $social_network_id
 * @property string|null $nick Nick o usuario dentro de la red social
 * @property string $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read SocialNetwork $socialNetwork
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereNick($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereSocialNetworkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserSocial extends BaseModel
{
    protected $table = 'user_social';

    protected $fillable = [
        'user_id',
        'social_network_id',
        'nick',
        'url',
    ];

    /**
     * Red social asociada a los datos.
     */
    public function socialNetwork(): BelongsTo
    {
        return $this->belongsTo(SocialNetwork::class, 'social_network_id', 'id');
    }

    /**
     * Usuario propietario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
