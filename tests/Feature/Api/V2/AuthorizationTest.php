<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Services\Hardware\DeviceTokenService;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Alcance real de un token robado.
 *
 * Es la pregunta que nadie había respondido con un test: si alguien saca el
 * token de un cacharro que está en la azotea, ¿hasta dónde llega? Cada test de
 * aquí fija una respuesta concreta.
 */
class AuthorizationTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function the_hardware_inventory_is_not_readable_with_any_token(): void
    {
        // Antes `GET /hardware/devices/{id}` no exigía `hardware:read` y encima
        // hacía `find($id)` sin filtrar por dueño: iterando el id salía el
        // inventario completo de todos los usuarios, con número de serie
        // (auditoría A3 y A9).
        $victim = $this->createAuthenticatedUser();
        $foreign = HardwareDevice::create([
            'user_id' => $victim->id,
            'name' => 'Estación de la montaña',
        ]);

        $attacker = $this->createAuthenticatedUser();

        $sinAbility = $this->moduleHeaders($attacker, TokenAbilities::WEATHERSTATION_WRITE);
        $this->getJson($this->apiUrl("hardware/devices/{$foreign->id}"), $sinAbility)->assertStatus(403);

        $conAbility = $this->moduleHeaders($attacker, TokenAbilities::HARDWARE_READ);
        $this->getJson($this->apiUrl("hardware/devices/{$foreign->id}"), $conAbility)->assertStatus(404);
    }

    #[Test]
    public function hardware_read_reads_the_own_device(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Portátil',
        ]);

        $response = $this->getJson(
            $this->apiUrl("hardware/devices/{$device->id}"),
            $this->moduleHeaders($user, TokenAbilities::HARDWARE_READ)
        );

        $this->assertSuccessResponse($response);
        $response->assertJsonPath('data.id', $device->id);
    }

    #[Test]
    public function a_token_bound_to_a_device_does_not_read_the_others_of_the_same_owner(): void
    {
        $user = $this->createAuthenticatedUser();
        $own = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Estación A']);
        $theOther = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Estación B']);

        $headers = $this->deviceHeaders($own, [TokenAbilities::HARDWARE_READ]);

        $this->getJson($this->apiUrl("hardware/devices/{$own->id}"), $headers)->assertStatus(200);
        $this->getJson($this->apiUrl("hardware/devices/{$theOther->id}"), $headers)->assertStatus(404);
    }

    #[Test]
    public function a_token_bound_to_a_device_does_not_write_on_another(): void
    {
        $user = $this->createAuthenticatedUser();
        $own = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Estación A']);
        $theOther = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Estación B']);

        $headers = $this->deviceHeaders($own, [TokenAbilities::HARDWARE_WRITE]);

        $this->putJson($this->apiUrl("hardware/devices/{$own->id}/status"), [
            'temp' => 30,
        ], $headers)->assertStatus(200);

        $this->putJson($this->apiUrl("hardware/devices/{$theOther->id}/status"), [
            'temp' => 30,
        ], $headers)->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function cannot_write_on_another_users_device(): void
    {
        $victim = $this->createAuthenticatedUser();
        $foreign = HardwareDevice::create(['user_id' => $victim->id, 'name' => 'Estación ajena']);

        $attacker = $this->createAuthenticatedUser();

        $this->putJson($this->apiUrl("hardware/devices/{$foreign->id}/status"), [
            'temp' => 30,
        ], $this->moduleHeaders($attacker, TokenAbilities::HARDWARE_WRITE))
            ->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function a_module_ability_does_not_work_for_another(): void
    {
        $user = $this->createAuthenticatedUser();
        $headers = $this->moduleHeaders($user, TokenAbilities::WEATHERSTATION_WRITE);

        $this->postJson($this->apiUrl('keycounter/keyboard-sessions'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('smartplant/plants/1/readings'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('airflight/aircrafts'), [], $headers)->assertStatus(403);
        $this->postJson($this->apiUrl('energy/readings'), [], $headers)->assertStatus(403);
        $this->getJson($this->apiUrl('hardware/devices'), $headers)->assertStatus(403);
    }

    #[Test]
    public function the_superadmin_does_not_lend_privileges_to_device_tokens(): void
    {
        // `Gate::before` devolvía true para superadmin sin mirar con qué token
        // llegaba la petición. Como el dueño de los cacharros ES superadmin,
        // eso anulaba las 16 policies justo para el principal del que hay que
        // defenderse.
        $jefe = $this->createAuthenticatedUser(1);
        $own = HardwareDevice::create(['user_id' => $jefe->id, 'name' => 'Estación A']);
        $theOther = HardwareDevice::create(['user_id' => $jefe->id, 'name' => 'Estación B']);

        $headers = $this->deviceHeaders($own, [TokenAbilities::HARDWARE_READ]);

        $this->getJson($this->apiUrl("hardware/devices/{$theOther->id}"), $headers)->assertStatus(404);
    }

    #[Test]
    public function a_device_token_cannot_be_issued_with_a_wildcard(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Estación A']);

        $this->expectException(\InvalidArgumentException::class);

        app(DeviceTokenService::class)->issue($device, ['*']);
    }

    #[Test]
    public function a_device_token_cannot_be_issued_with_the_session_ability(): void
    {
        $user = $this->createAuthenticatedUser();
        $device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Estación A']);

        $this->expectException(\InvalidArgumentException::class);

        app(DeviceTokenService::class)->issue($device, [TokenAbilities::SESSION]);
    }
}
