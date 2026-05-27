<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * Wrapper de Sanctum\PersonalAccessToken para usarlo como modelo de un
 * Filament Resource (fix_10 / fase 13).
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
