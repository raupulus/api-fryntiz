<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Wrapper de Sanctum\PersonalAccessToken para usarlo como modelo de un
 * Filament Resource (fix_10 / fase 13).
 *
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property array<array-key, mixed>|null $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $user_name
 * @property-read Model|\Eloquent $tokenable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereAbilities($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereTokenableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereTokenableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiToken whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ApiToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected $appends = ['user_name'];

    public function getUserNameAttribute(): ?string
    {
        if ($this->tokenable_type === User::class || $this->tokenable_type === User::class) {
            return optional(User::find($this->tokenable_id))->name;
        }

        return null;
    }
}
