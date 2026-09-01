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
    public function el_token_emitido_queda_ligado_a_su_dispositivo(): void
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
    public function nunca_emite_la_ability_de_sesion(): void
    {
        // Un cacharro con la ability de sesión podría cerrar la sesión de su
        // dueño y listar sus tokens. No debe poder pedirla ni de casualidad.
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, [TokenAbilities::SESSION]);
    }

    #[Test]
    public function nunca_emite_el_comodin(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, ['*']);
    }

    #[Test]
    public function rechaza_una_ability_que_no_esta_en_el_catalogo(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, ['inventada:write']);
    }

    #[Test]
    public function exige_al_menos_una_ability(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->expectException(InvalidArgumentException::class);

        $this->service->issue($device, []);
    }

    #[Test]
    public function un_dispositivo_sin_dueno_no_puede_tener_token(): void
    {
        // Sin propietario no hay a quién colgarle el token, y un token
        // huérfano no habría manera de revocarlo desde ninguna cuenta.
        $device = $this->makeDevice(null);

        $this->expectException(RuntimeException::class);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE]);
    }

    #[Test]
    public function no_duplica_abilities_repetidas(): void
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
    public function respeta_la_caducidad_pedida(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);
        $caduca = now()->addDays(30);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE], $caduca);

        $this->assertNotNull($user->tokens()->latest('id')->first()->expires_at);
    }

    #[Test]
    public function por_defecto_el_token_de_cacharro_no_caduca(): void
    {
        // Decisión D1: están en sitios a los que no se sube a reflashear.
        $user = $this->createAuthenticatedUser();
        $device = $this->makeDevice($user);

        $this->service->issue($device, [TokenAbilities::WEATHERSTATION_WRITE]);

        $this->assertNull($user->tokens()->latest('id')->first()->expires_at);
    }
}
