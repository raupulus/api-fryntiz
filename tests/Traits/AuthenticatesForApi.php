<?php

namespace Tests\Traits;

use App\Models\User;

trait AuthenticatesForApi
{
    protected function createAuthenticatedUser(int $role = 3): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role])->save();
        return $user->fresh();
    }

    protected function authenticatedHeaders(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    protected function guestHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    /**
     * Crea un SuperAdmin (role=1) y devuelve headers autenticados.
     */
    protected function asSuperAdmin(): array
    {
        return $this->authenticatedHeaders($this->createAuthenticatedUser(1));
    }

    /**
     * Crea un Admin (role=2) y devuelve headers autenticados.
     */
    protected function asAdmin(): array
    {
        return $this->authenticatedHeaders($this->createAuthenticatedUser(2));
    }

    /**
     * Crea un User normal (role=3) y devuelve headers autenticados.
     */
    protected function asUser(): array
    {
        return $this->authenticatedHeaders($this->createAuthenticatedUser(3));
    }
}
