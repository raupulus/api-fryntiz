<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Enums\UserRoleEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Services\Hardware\DeviceTokenService;
use App\Support\Auth\TokenAbilities;

/**
 * Ayudas de autenticación para los tests de la API V2.
 *
 * Los tokens que se emiten aquí llevan las mismas abilities que los de verdad.
 * Antes se emitía `createToken('test-token')`, que en Sanctum equivale a `*`:
 * cualquier test pasaba cualquier `ability:`, así que los tests no probaban el
 * control de alcance, sólo su ausencia.
 */
trait AuthenticatesForApi
{
    protected function createAuthenticatedUser(int $role = UserRoleEnum::User->value): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role_id' => $role, 'is_active' => true])->save();

        return $user->fresh();
    }

    /**
     * Cabeceras con un token de sesión humana (el que emite el login).
     */
    protected function authenticatedHeaders(User $user): array
    {
        $token = $user->createToken('test-session', TokenAbilities::forSession())->plainTextToken;

        return $this->headersWith($token);
    }

    /**
     * Cabeceras con un token de dispositivo IoT, ligado a un dispositivo
     * concreto y con las abilities de módulo indicadas.
     *
     * @param  array<int, string>  $abilities
     */
    protected function deviceHeaders(HardwareDevice $device, array $abilities): array
    {
        $token = app(DeviceTokenService::class)->issue($device, $abilities)->plainTextToken;

        return $this->headersWith($token);
    }

    /**
     * Cabeceras con un token de módulo sin ligar a un dispositivo concreto
     * (alcanza todos los dispositivos de su dueño). Es el token que se emite
     * cuando un cacharro gestiona varias placas.
     */
    protected function moduleHeaders(User $user, string ...$abilities): array
    {
        return $this->headersWithAbilities($user, $abilities);
    }

    /**
     * Cabeceras con un token de abilities arbitrarias, para probar casos límite.
     *
     * @param  array<int, string>  $abilities
     */
    protected function headersWithAbilities(User $user, array $abilities): array
    {
        $token = $user->createToken('test-token', $abilities)->plainTextToken;

        return $this->headersWith($token);
    }

    protected function guestHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }

    /**
     * Crea un SuperAdmin y devuelve cabeceras de sesión.
     */
    protected function asSuperAdmin(): array
    {
        return $this->authenticatedHeaders($this->createAuthenticatedUser(UserRoleEnum::SuperAdmin->value));
    }

    /**
     * Crea un Admin y devuelve cabeceras de sesión.
     */
    protected function asAdmin(): array
    {
        return $this->authenticatedHeaders($this->createAuthenticatedUser(UserRoleEnum::Admin->value));
    }

    /**
     * Crea un usuario normal y devuelve cabeceras de sesión.
     */
    protected function asUser(): array
    {
        return $this->authenticatedHeaders($this->createAuthenticatedUser(UserRoleEnum::User->value));
    }

    /**
     * @return array<string, string>
     */
    private function headersWith(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }
}
