<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Pruebas de integración para detección de reinicio de odómetro y gestión multisensión (D115, Fase 6).
 */
class EnergyHistoricalResetTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $generatorElement;

    private HardwareEnergy $batteryElement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20LI Test Reset',
        ]);

        $this->generatorElement = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);

        $this->batteryElement = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.8,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_a_new_session_when_generator_historical_wh_drops_drastically(): void
    {
        // 1. Primera lectura: odómetro reporta 10,000 Wh acumulados
        $firstPayload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.5,
                    'amperage' => 5.0,
                    'power' => 122.5,
                    'historical_energy_wh' => 10000.0,
                ],
            ],
        ];

        $response1 = $this->postJson(
            $this->apiUrl('energy/readings'),
            $firstPayload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response1->assertCreated();

        $sessionsAfterFirst = HardwareEnergyHistorical::query()
            ->where('hardware_device_id', $this->device->id)
            ->where('hardware_energy_id', $this->generatorElement->id)
            ->orderBy('session_index')
            ->get();

        $this->assertCount(1, $sessionsAfterFirst);
        $this->assertSame(1, $sessionsAfterFirst[0]->session_index);
        $this->assertSame(10000.0, (float) $sessionsAfterFirst[0]->energy_wh);

        // 2. Segunda lectura: El hardware se reinició o se reseteó el odómetro a 500 Wh
        $resetPayload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.6,
                    'amperage' => 5.1,
                    'power' => 125.46,
                    'historical_energy_wh' => 500.0,
                ],
            ],
        ];

        $response2 = $this->postJson(
            $this->apiUrl('energy/readings'),
            $resetPayload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response2->assertCreated();

        // 3. Verificamos que se preservó la sesión 1 intacta y se creó la sesión 2
        $sessionsAfterReset = HardwareEnergyHistorical::query()
            ->where('hardware_device_id', $this->device->id)
            ->where('hardware_energy_id', $this->generatorElement->id)
            ->orderBy('session_index')
            ->get();

        $this->assertCount(2, $sessionsAfterReset);

        $session1 = $sessionsAfterReset[0];
        $session2 = $sessionsAfterReset[1];

        $this->assertSame(1, $session1->session_index);
        $this->assertSame(10000.0, (float) $session1->energy_wh);

        $this->assertSame(2, $session2->session_index);
        $this->assertSame(500.0, (float) $session2->energy_wh);

        // 4. Tercera lectura normal incremental: se mantiene en sesión 2 y no crea sesión 3
        $incrementalPayload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'generator' => [
                    'voltage' => 24.6,
                    'amperage' => 5.1,
                    'power' => 125.46,
                    'historical_energy_wh' => 550.0,
                ],
            ],
        ];

        $response3 = $this->postJson(
            $this->apiUrl('energy/readings'),
            $incrementalPayload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response3->assertCreated();

        $sessionsFinal = HardwareEnergyHistorical::query()
            ->where('hardware_device_id', $this->device->id)
            ->where('hardware_energy_id', $this->generatorElement->id)
            ->orderBy('session_index')
            ->get();

        $this->assertCount(2, $sessionsFinal);
        $this->assertSame(550.0, (float) $sessionsFinal[1]->energy_wh);

        // 5. Total acumulado global sumando todas las sesiones históricas
        $totalHistoricalWh = (float) HardwareEnergyHistorical::query()
            ->where('hardware_device_id', $this->device->id)
            ->where('hardware_energy_id', $this->generatorElement->id)
            ->sum('energy_wh');

        $this->assertSame(10550.0, $totalHistoricalWh);
    }

    #[Test]
    public function it_creates_a_new_session_when_battery_historical_ah_drops_drastically(): void
    {
        // 1. Primera lectura de batería: 800 Ah acumulados
        $firstPayload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'battery' => [
                    'voltage' => 13.2,
                    'amperage' => 10.0,
                    'soc' => 95,
                    'historical_energy_ah' => 800.0,
                ],
            ],
        ];

        $response1 = $this->postJson(
            $this->apiUrl('energy/readings'),
            $firstPayload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response1->assertCreated();

        // 2. Segunda lectura: reset de batería a 15 Ah
        $resetPayload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'battery' => [
                    'voltage' => 13.2,
                    'amperage' => 10.0,
                    'soc' => 95,
                    'historical_energy_ah' => 15.0,
                ],
            ],
        ];

        $response2 = $this->postJson(
            $this->apiUrl('energy/readings'),
            $resetPayload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response2->assertCreated();

        $batterySessions = HardwareEnergyHistorical::query()
            ->where('hardware_device_id', $this->device->id)
            ->where('hardware_energy_id', $this->batteryElement->id)
            ->orderBy('session_index')
            ->get();

        $this->assertCount(2, $batterySessions);
        $this->assertSame(1, $batterySessions[0]->session_index);
        $this->assertSame(800.0, (float) $batterySessions[0]->energy_ah);

        $this->assertSame(2, $batterySessions[1]->session_index);
        $this->assertSame(15.0, (float) $batterySessions[1]->energy_ah);
    }
}
