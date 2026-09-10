<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Aislamiento entre los tokens de dos cacharros del mismo dueño.
 *
 * El escenario real: un controlador solar en el campo (token de subida de
 * lecturas) y un portátil que sube pulsaciones de KeyCounter. Si roban el
 * primero —que está físicamente accesible— no puede tocar ni leer nada del
 * segundo.
 *
 * Quien pone el límite es la ability `device:{id}`, no la de módulo: sin ella,
 * `hardware:write` alcanzaría a cualquier dispositivo de la misma cuenta.
 */
class TokenIsolationTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $solarDevice;

    private HardwareDevice $laptopDevice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        $type = HardwareType::create(['name' => 'Controlador Solar', 'description' => 'Tipo de prueba']);

        $this->solarDevice = HardwareDevice::create([
            'user_id' => $this->user->id,
            'hardware_type_id' => $type->id,
            'name' => 'renogy',
            'name_friendly' => 'Renogy',
        ]);

        $this->laptopDevice = HardwareDevice::create([
            'user_id' => $this->user->id,
            'hardware_type_id' => $type->id,
            'name' => 'thinkpad',
            'name_friendly' => 'Thinkpad',
        ]);
    }

    #[Test]
    public function the_solar_device_token_uploads_its_own_readings(): void
    {
        $response = $this->postJson(
            $this->apiUrl('energy/solar-readings'),
            $this->solarReading($this->solarDevice),
            $this->solarDeviceToken()
        );

        $response->assertSuccessful();
    }

    #[Test]
    public function the_solar_device_token_cannot_write_readings_for_another_device(): void
    {
        $response = $this->postJson(
            $this->apiUrl('energy/solar-readings'),
            $this->solarReading($this->laptopDevice),
            $this->solarDeviceToken()
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function the_solar_device_token_does_not_touch_any_devices_status(): void
    {
        // Ni el del portátil ni el suyo propio: subir vatios y reescribir el
        // último estado conocido del aparato son permisos distintos desde que
        // energía es su propio módulo.
        foreach ([$this->laptopDevice, $this->solarDevice] as $device) {
            $response = $this->putJson(
                $this->apiUrl('hardware/devices/'.$device->id.'/status'),
                ['uptime' => 120],
                $this->solarDeviceToken()
            );

            $this->assertErrorResponse($response, 403);
        }
    }

    #[Test]
    public function the_solar_device_token_reads_its_own_readings_but_not_the_others(): void
    {
        $this->postJson(
            $this->apiUrl('energy/solar-readings'),
            $this->solarReading($this->solarDevice),
            $this->solarDeviceToken()
        )->assertSuccessful();

        // Con `energy:read` sólo alcanza las de los dispositivos que
        // declara su token.
        $headers = $this->headersWithAbilities($this->user, [
            TokenAbilities::ENERGY_READ,
            TokenAbilities::forDevice($this->solarDevice),
        ]);

        $response = $this->getJson($this->apiUrl('energy/solar-readings'), $headers);

        $response->assertSuccessful();

        foreach ($response->json('data') as $reading) {
            $this->assertSame($this->solarDevice->id, $reading['hardware_device_id']);
        }
    }

    #[Test]
    public function the_energy_token_cannot_read_the_inventory(): void
    {
        $headers = $this->headersWithAbilities($this->user, [
            TokenAbilities::ENERGY_READ,
            TokenAbilities::forDevice($this->solarDevice),
        ]);

        $this->assertErrorResponse($this->getJson($this->apiUrl('hardware/devices'), $headers), 403);
    }

    #[Test]
    public function the_solar_device_token_cannot_list_its_owners_fleet(): void
    {
        // Sin `hardware:read` no llega ni a la ruta del inventario.
        $response = $this->getJson($this->apiUrl('hardware/devices'), $this->solarDeviceToken());

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function the_solar_device_token_cannot_read_keycounter_sessions(): void
    {
        $response = $this->getJson($this->apiUrl('keycounter/keyboard-sessions'), $this->solarDeviceToken());

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function the_solar_device_token_cannot_upload_the_laptops_keystrokes(): void
    {
        $response = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->keyboardSession($this->laptopDevice),
            $this->solarDeviceToken()
        );

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function the_laptop_token_uploads_its_own_keystrokes_but_not_the_others(): void
    {
        $headers = $this->headersWithAbilities($this->user, [
            TokenAbilities::KEYCOUNTER_WRITE,
            TokenAbilities::forDevice($this->laptopDevice),
        ]);

        $ownSession = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->keyboardSession($this->laptopDevice),
            $headers
        );
        $ownSession->assertSuccessful();

        $foreignSession = $this->postJson(
            $this->apiUrl('keycounter/keyboard-sessions'),
            $this->keyboardSession($this->solarDevice),
            $headers
        );
        $this->assertErrorResponse($foreignSession, 422);
        $foreignSession->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function the_laptop_token_cannot_write_solar_readings(): void
    {
        $headers = $this->headersWithAbilities($this->user, [
            TokenAbilities::KEYCOUNTER_WRITE,
            TokenAbilities::forDevice($this->laptopDevice),
        ]);

        $response = $this->postJson(
            $this->apiUrl('energy/solar-readings'),
            $this->solarReading($this->laptopDevice),
            $headers
        );

        $this->assertErrorResponse($response, 403);
    }

    /**
     * Token tal y como se emite para un controlador solar: escritura del
     * módulo Energía y nada más, ligado a ese aparato.
     *
     * Hasta el 2026-09-06 llevaba `hardware:write`, que además de las lecturas
     * le abría el estado del aparato. Son dos permisos distintos y se conceden
     * por separado.
     *
     * @return array<string, string>
     */
    private function solarDeviceToken(): array
    {
        return $this->headersWithAbilities($this->user, [
            TokenAbilities::ENERGY_WRITE,
            TokenAbilities::forDevice($this->solarDevice),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function solarReading(HardwareDevice $device): array
    {
        return [
            'hardware_device_id' => $device->id,
            'date' => now()->toDateString(),
            'read_at' => now()->format('Y-m-d H:i:s'),
            'battery_voltage' => 13.2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function keyboardSession(HardwareDevice $device): array
    {
        return [
            'hardware_device_id' => $device->id,
            'user_id' => $this->user->id,
            'start_at' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
            'end_at' => now()->format('Y-m-d H:i:s'),
            'duration' => 300,
            'pulsations' => 500,
            'pulsations_special_keys' => 20,
            'pulsation_average' => 1.7,
            'score' => 50,
            'weekday' => 1,
        ];
    }
}
