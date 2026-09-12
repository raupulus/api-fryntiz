<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Pruebas de seguridad, autorización y aislamiento multiusuario en endpoints de energía (D115, Fase 6).
 */
class EnergySecurityTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $userA;

    private User $userB;

    private HardwareDevice $deviceA;

    private HardwareDevice $deviceB;

    private HardwareEnergy $energyElementA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = $this->createAuthenticatedUser(3);
        $this->userB = $this->createAuthenticatedUser(3);

        $type = HardwareType::create(['name' => 'Monitor Energético', 'description' => 'Dispositivo de telemetría']);

        $this->deviceA = HardwareDevice::create([
            'user_id' => $this->userA->id,
            'hardware_type_id' => $type->id,
            'name' => 'device-user-a',
            'name_friendly' => 'Dispositivo A',
        ]);

        $this->deviceB = HardwareDevice::create([
            'user_id' => $this->userB->id,
            'hardware_type_id' => $type->id,
            'name' => 'device-user-b',
            'name_friendly' => 'Dispositivo B',
        ]);

        $this->energyElementA = HardwareEnergy::create([
            'hardware_device_id' => $this->deviceA->id,
            'hardware_device_monitorized_id' => $this->deviceA->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_rejects_reading_creation_without_energy_write_ability(): void
    {
        $payload = [
            'hardware_device_id' => $this->deviceA->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.0,
                    'amperage' => 5.0,
                ],
            ],
        ];

        // Token sólo con energy:read (sin energy:write)
        $headers = $this->headersWithAbilities($this->userA, [
            TokenAbilities::ENERGY_READ,
            TokenAbilities::forDevice($this->deviceA),
        ]);

        $response = $this->postJson($this->apiUrl('energy/readings'), $payload, $headers);

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function it_rejects_reading_creation_for_device_of_another_user(): void
    {
        $payload = [
            'hardware_device_id' => $this->deviceA->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.0,
                    'amperage' => 5.0,
                ],
            ],
        ];

        // Usuario B intenta enviar lecturas al dispositivo del Usuario A
        $headers = $this->headersWithAbilities($this->userB, [
            TokenAbilities::ENERGY_WRITE,
            TokenAbilities::forDevice($this->deviceB),
        ]);

        $response = $this->postJson($this->apiUrl('energy/readings'), $payload, $headers);

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function it_rejects_reading_creation_when_device_ability_does_not_match_payload_device(): void
    {
        $deviceA2 = HardwareDevice::create([
            'user_id' => $this->userA->id,
            'name' => 'device-user-a2',
        ]);

        $payload = [
            'hardware_device_id' => $deviceA2->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.0,
                    'amperage' => 5.0,
                ],
            ],
        ];

        // Token de Usuario A pero acotado a deviceA (no deviceA2)
        $headers = $this->headersWithAbilities($this->userA, [
            TokenAbilities::ENERGY_WRITE,
            TokenAbilities::forDevice($this->deviceA),
        ]);

        $response = $this->postJson($this->apiUrl('energy/readings'), $payload, $headers);

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['hardware_device_id']);
    }

    #[Test]
    public function it_rejects_reading_listing_without_energy_read_ability(): void
    {
        // Token sólo con energy:write (sin energy:read)
        $headers = $this->headersWithAbilities($this->userA, [
            TokenAbilities::ENERGY_WRITE,
            TokenAbilities::forDevice($this->deviceA),
        ]);

        $response = $this->getJson($this->apiUrl('energy/readings'), $headers);

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function it_enforces_multi_user_isolation_on_index_listing(): void
    {
        // 1. Usuario A sube una lectura
        $payload = [
            'hardware_device_id' => $this->deviceA->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.0,
                    'amperage' => 5.0,
                ],
            ],
        ];

        $headersAWrite = $this->headersWithAbilities($this->userA, [
            TokenAbilities::ENERGY_WRITE,
            TokenAbilities::forDevice($this->deviceA),
        ]);

        $this->postJson($this->apiUrl('energy/readings'), $payload, $headersAWrite)->assertCreated();

        // 2. Usuario B consulta el listado con permisos de lectura para su propio dispositivo
        $headersBRead = $this->headersWithAbilities($this->userB, [
            TokenAbilities::ENERGY_READ,
            TokenAbilities::forDevice($this->deviceB),
        ]);

        $responseB = $this->getJson($this->apiUrl('energy/readings'), $headersBRead);

        $responseB->assertSuccessful();
        $this->assertCount(0, $responseB->json('data'));

        // 3. Usuario A consulta y sí ve su lectura
        $headersARead = $this->headersWithAbilities($this->userA, [
            TokenAbilities::ENERGY_READ,
            TokenAbilities::forDevice($this->deviceA),
        ]);

        $responseA = $this->getJson($this->apiUrl('energy/readings'), $headersARead);

        $responseA->assertSuccessful();
        $this->assertCount(1, $responseA->json('data'));
        $this->assertSame($this->deviceA->id, $responseA->json('data.0.hardware_device_id'));
    }
}
