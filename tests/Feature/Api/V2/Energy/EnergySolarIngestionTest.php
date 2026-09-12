<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * Pruebas de integración para ingesta completa Renogy Rover (D115, Fase 6).
 */
class EnergySolarIngestionTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $generatorElement;

    private HardwareEnergy $batteryElement;

    private HardwareEnergy $loadElement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Renogy Rover 20LI',
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
            'voltage_min' => 11.0,
            'voltage_max' => 14.4,
            'capacity_ah' => 100.0,
            'is_active' => true,
        ]);

        $this->loadElement = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_persists_a_complete_three_block_solar_telemetry_payload(): void
    {
        $payload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 300,
                'generator' => [
                    'voltage' => 34.5,
                    'amperage' => 4.2,
                    'power' => 144.9,
                    'temperature' => 38.0,
                    'fan' => 1,
                    'charging_status' => 3,
                    'charging_status_label' => 'mppt',
                    'light_status' => false,
                    'today_energy_wh' => 1250.0,
                    'historical_energy_wh' => 45000.0,
                ],
                'battery' => [
                    'voltage' => 13.4,
                    'soc' => 92,
                    'temperature' => 24.5,
                    'charging_status' => 3,
                    'today_energy_ah' => 40.0,
                    'historical_energy_ah' => 1500.0,
                    'battery_full_charges' => 25,
                    'battery_over_discharges' => 1,
                ],
                'loads' => [
                    [
                        'channel' => 0,
                        'voltage' => 12.1,
                        'amperage' => 2.5,
                        'power' => 30.25,
                        'temperature' => 28.0,
                        'fan' => 0,
                        'today_energy_wh' => 350.0,
                    ],
                ],
            ],
        ];

        $response = $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        // Verificar 3 lecturas persistidas en hardware_energy_readings
        $readings = HardwareEnergyReading::query()->where('hardware_device_id', $this->device->id)->get();
        $this->assertCount(3, $readings);

        $genReading = $readings->firstWhere('hardware_energy_id', $this->generatorElement->id);
        $this->assertNotNull($genReading);
        $this->assertSame(144.9, (float) $genReading->power);
        $this->assertSame(3, $genReading->charging_status);
        $this->assertFalse($genReading->light_status);

        $batReading = $readings->firstWhere('hardware_energy_id', $this->batteryElement->id);
        $this->assertNotNull($batReading);
        $this->assertSame(13.4, (float) $batReading->battery_voltage);
        $this->assertSame(92, $batReading->battery_percentage);
        $this->assertSame(24.5, (float) $batReading->temperature);

        $loadReading = $readings->firstWhere('hardware_energy_id', $this->loadElement->id);
        $this->assertNotNull($loadReading);
        $this->assertSame(30.25, (float) $loadReading->power);
        $this->assertSame(2.5, (float) $loadReading->amperage);

        // Verificar que los acumulados diarios existen para cada rol
        $this->assertSame(1250.0, (float) HardwareEnergyToday::query()->where('hardware_energy_id', $this->generatorElement->id)->value('energy_wh'));
        $this->assertSame(40.0, (float) HardwareEnergyToday::query()->where('hardware_energy_id', $this->batteryElement->id)->value('energy_ah'));
        $this->assertSame(350.0, (float) HardwareEnergyToday::query()->where('hardware_energy_id', $this->loadElement->id)->value('energy_wh'));

        // Verificar históricos y ciclos de batería
        $batHist = HardwareEnergyHistorical::query()->where('hardware_energy_id', $this->batteryElement->id)->first();
        $this->assertNotNull($batHist);
        $this->assertSame(1500.0, (float) $batHist->energy_ah);
        $this->assertSame(25, $batHist->number_battery_full_charges);
        $this->assertSame(1, $batHist->number_battery_over_discharges);
    }

    #[Test]
    public function it_infers_battery_soc_from_voltage_calibration_when_soc_is_omitted(): void
    {
        // Con voltage_min = 11.0 y voltage_max = 14.4:
        // Una tensión de 12.7 V está a (12.7 - 11.0) / (14.4 - 11.0) = 1.7 / 3.4 = 50 %
        $payload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'battery' => [
                    'voltage' => 12.7,
                    'soc' => null,
                ],
            ],
        ];

        $response = $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );

        $response->assertStatus(201);

        $batReading = HardwareEnergyReading::query()
            ->where('hardware_energy_id', $this->batteryElement->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($batReading);
        $this->assertSame(50, $batReading->battery_percentage);
    }
}
