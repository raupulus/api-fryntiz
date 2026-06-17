<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Models\User;
use App\Models\UserRole;

/**
 * Trait para tests de Filament — crea y autentica un superadmin.
 */
trait LoginAsAdmin
{
    protected User $admin;

    protected function loginAsAdmin(): User
    {
        UserRole::firstOrCreate(['id' => 1], [
            'name' => 'superadmin',
            'slug' => 'superadmin',
            'display_name' => 'SuperAdmin',
        ]);

        $this->admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($this->admin);

        return $this->admin;
    }
}
