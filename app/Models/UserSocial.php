<?php

namespace App\Models;

use App\Models\Content\Content;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocial extends Model
{
    protected $table = 'user_social';

    /**
     * Red social asociada a los datos.
     *
     * @return BelongsTo
     */
    public function socialNetwork(): BelongsTo
    {
        return $this->belongsTo(SocialNetwork::class, 'social_network_id', 'id');
    }
}
