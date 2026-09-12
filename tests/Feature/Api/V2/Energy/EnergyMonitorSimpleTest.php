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
 * Pruebas de integración para ingesta IoT simple con cálculo de duración (D115, Fase 6).
 */
class EnergyMonitorSimpleTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $element;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'ESP32 INA219 Monitor',
        ]);

        $this->element = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_persists_a_simple_load_telemetry_and_calculates_power_wh_and_ah(): void
    {
        $payload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'loads' => [
                    [
                        'channel' => 0,
                        'voltage' => 12.0,
                        'amperage' => 2.0,
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
            ->assertJsonPath('data.0.measured.amperage', fn ($v) => (float) $v === 2.0)
            ->assertJsonPath('data.0.measured.voltage', fn ($v) => (float) $v === 12.0)
            ->assertJsonPath('data.0.measured.power', fn ($v) => (float) $v === 24.0)
            ->assertJsonPath('data.0.derived.energy_wh', fn ($v) => abs((float) $v - 0.4) < 0.001)
            ->assertJsonPath('data.0.derived.energy_ah', fn ($v) => abs((float) $v - 0.0333) < 0.001);

        // Comprobación de persistencia en readings
        $reading = HardwareEnergyReading::query()->where('hardware_device_id', $this->device->id)->latest('id')->first();
        $this->assertNotNull($reading);
        $this->assertSame(24.0, (float) $reading->power);
        $this->assertEqualsWithDelta(0.4, (float) $reading->energy_wh, 0.0001);
        $this->assertEqualsWithDelta(0.0333, (float) $reading->energy_ah, 0.001);

        // Comprobación de persistencia en today
        $today = HardwareEnergyToday::query()->where('hardware_device_id', $this->device->id)->first();
        $this->assertNotNull($today);
        $this->assertSame(1, $today->readings_count);
        $this->assertEqualsWithDelta(0.4, (float) $today->energy_wh, 0.0001);
        $this->assertSame(12.0, (float) $today->voltage_min);
        $this->assertSame(12.0, (float) $today->voltage_max);

        // Comprobación de persistencia en historical
        $hist = HardwareEnergyHistorical::query()->where('hardware_device_id', $this->device->id)->first();
        $this->assertNotNull($hist);
        $this->assertSame(1, $hist->session_index);
        $this->assertEqualsWithDelta(0.4, (float) $hist->energy_wh, 0.0001);
    }

    #[Test]
    public function it_accumulates_daily_and_historical_totals_across_multiple_readings(): void
    {
        $payload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 60,
                'loads' => [
                    [
                        'channel' => 0,
                        'voltage' => 12.0,
                        'amperage' => 2.0,
                    ],
                ],
            ],
        ];

        // Primera lectura
        $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        )->assertStatus(201);

        // Segunda lectura
        $this->postJson(
            $this->apiUrl('energy/readings'),
            $payload,
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        )->assertStatus(201);

        $today = HardwareEnergyToday::query()->where('hardware_device_id', $this->device->id)->first();
        $this->assertSame(2, $today->readings_count);
        $this->assertEqualsWithDelta(0.8, (float) $today->energy_wh, 0.0001);

        $hist = HardwareEnergyHistorical::query()->where('hardware_device_id', $this->device->id)->first();
        $this->assertSame(2, $hist->readings_count);
        $this->assertEqualsWithDelta(0.8, (float) $hist->energy_wh, 0.0001);
    }

    #[Test]
    public function it_uses_nominal_voltage_fallback_when_measure_is_omitted(): void
    {
        $payload = [
            'hardware_device_id' => $this->device->id,
            'energy' => [
                'duration' => 120,
                'loads' => [
                    [
                        'channel' => 0,
                        'voltage' => null,
                        'amperage' => 1.5,
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
            ->assertJsonPath('data.0.sources.voltage', 'nominal')
            ->assertJsonPath('data.0.measured.voltage', fn ($v) => (float) $v === 12.0)
            ->assertJsonPath('data.0.measured.power', fn ($v) => (float) $v === 18.0)
            ->assertJsonPath('data.0.derived.energy_wh', fn ($v) => abs((float) $v - 0.6) < 0.001);
    }
}
