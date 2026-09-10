<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Services\Hardware\DeviceTokenService;
use App\Support\Auth\TokenAbilities;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\AuthenticatesForApi;

/**
 * Es la única puerta por la que se emiten tokens de cacharro, así que lo que
 * NO debe salir por aquí importa tanto como lo que sí.
 */
class DeviceTokenServiceTest extends TestCase
{
    use AuthenticatesForApi;
    use RefreshDatabase;

    private DeviceTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // createAuthenticatedUser() inserta en users, que tiene FK a roles.
        (new RolesTableSeeder)->run();

        $this->service = app(DeviceTokenService::class);
    }

    private function makeDevice(?User $owner = null): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'user_id' => $owner?->id,
            'name' => 'Cacharro '.uniqid(),
        ]);
    }

    #[Test]
    public function the_issued_token_is_bound_to_its_device(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE]);

        $token = $user->tokens()->latest('id')->first();

        $this->assertContains('device:'.$device->id, $token->abilities);
        $this->assertContains(TokenAbilities::WEATHERSTATION_WRITE, $token->abilities);
        $this->assertSame('device:'.$device->id, $token->name);
    }

    #[Test]
    public function it_never_issues_the_session_ability(): void
    {
        // Un cacharro con la ability de sesión podría cerrar la sesión de su
        // dueño y listar sus tokens. No debe poder pedirla ni de casualidad.
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, [TokenAbilities::SESSION]);
    }

    #[Test]
    public function it_never_issues_the_wildcard(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, ['*']);
    }

    #[Test]
    public function it_rejects_an_ability_not_in_the_catalog(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, ['inventada:write']);
    }

    #[Test]
    public function it_requires_at_least_one_ability(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, []);
    }

    #[Test]
    public function a_device_without_an_owner_cannot_have_a_token(): void
    {
        // Sin propietario no hay a quién colgarle el token, y un token
        // huérfano no habría manera de revocarlo desde ninguna cuenta.
        $device = $this->makeDevice(null);

        $this->expectException(RuntimeException::class);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE]);
    }

    #[Test]
    public function it_does_not_duplicate_repeated_abilities(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->service->issue($device, [
            TokenAbilities::WEATHERSTATION_WRITE,
            TokenAbilities::WEATHERSTATION_WRITE,
        ]);

        $abilities = $user->tokens()->latest('id')->first()->abilities;

        $this->assertSame(
            count($abilities),
            count(array_unique($abilities))
        );
    }

    #[Test]
    public function it_respects_the_requested_expiration(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);
        $expiresAt = now()->addDays(30);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE], $expiresAt);

        $this->assertNotNull($user->tokens()->latest('id')->first()->expires_at);
    }

    #[Test]
    public function by_default_a_device_token_never_expires(): void
    {
        // Decisión D1: están en sitios a los que no se sube a reflashear.
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE]);

        $this->assertNull($user->tokens()->latest('id')->first()->expires_at);
    }
}
