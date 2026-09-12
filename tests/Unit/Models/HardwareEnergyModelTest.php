<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HardwareEnergyModelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Solar Rig Monitor',
        ]);
    }

    #[Test]
    public function it_calculates_capacity_wh_dynamically_from_capacity_ah_and_nominal_voltage(): void
    {
        $battery = new HardwareEnergy([
            'role' => HardwareEnergy::ROLE_BATTERY,
            'nominal_voltage' => 12.8,
            'capacity_ah' => 100.5,
        ]);

        $this->assertSame(1286.4, $battery->capacity_wh);
    }

    #[Test]
    public function it_returns_null_for_capacity_wh_if_capacity_ah_is_missing(): void
    {
        $battery = new HardwareEnergy([
            'role' => HardwareEnergy::ROLE_BATTERY,
            'nominal_voltage' => 12.8,
            'capacity_ah' => null,
        ]);

        $this->assertNull($battery->capacity_wh);
    }

    #[Test]
    public function it_returns_null_for_capacity_wh_if_nominal_voltage_is_missing(): void
    {
        $battery = new HardwareEnergy([
            'role' => HardwareEnergy::ROLE_BATTERY,
            'nominal_voltage' => null,
            'capacity_ah' => 100.0,
        ]);

        $this->assertNull($battery->capacity_wh);
    }

    #[Test]
    public function it_correctly_casts_capacity_ah_and_auto_calculate_history(): void
    {
        $element = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'capacity_ah' => '50.125',
            'auto_calculate_history' => true,
        ]);

        $element->refresh();

        $this->assertSame(50.125, $element->capacity_ah);
        $this->assertTrue($element->auto_calculate_history);
    }

    #[Test]
    public function it_scopes_elements_by_role(): void
    {
        $gen = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
        ]);

        $load = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 1,
        ]);

        $bat = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 2,
        ]);

        $this->assertTrue(HardwareEnergy::generators()->where('id', $gen->id)->exists());
        $this->assertFalse(HardwareEnergy::generators()->where('id', $load->id)->exists());

        $this->assertTrue(HardwareEnergy::loads()->where('id', $load->id)->exists());
        $this->assertFalse(HardwareEnergy::loads()->where('id', $bat->id)->exists());

        $this->assertTrue(HardwareEnergy::batteries()->where('id', $bat->id)->exists());
        $this->assertFalse(HardwareEnergy::batteries()->where('id', $gen->id)->exists());
    }

    #[Test]
    public function it_supports_bidirectional_relationships_with_readings_today_and_historical(): void
    {
        $element = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
        ]);

        $reading = HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 24.5,
            'amperage' => 5.0,
            'power' => 122.5,
            'delta_seconds' => 60,
            'energy_wh' => 2.0416,
            'energy_ah' => 0.0833,
        ]);

        $today = HardwareEnergyToday::recalculateForElement($this->device->id, $element->id, [
            'voltage' => 24.5,
            'amperage' => 5.0,
            'power' => 122.5,
            'energy_wh' => 2.0416,
            'energy_ah' => 0.0833,
        ]);

        $hist = HardwareEnergyHistorical::accumulateForElement($this->device->id, $element->id, [
            'voltage' => 24.5,
            'amperage' => 5.0,
            'power' => 122.5,
            'energy_wh' => 2.0416,
            'energy_ah' => 0.0833,
        ]);

        // Probar relaciones hasMany en HardwareEnergy
        $this->assertTrue($element->readings()->where('id', $reading->id)->exists());
        $this->assertTrue($element->today()->where('id', $today->id)->exists());
        $this->assertTrue($element->historical()->where('id', $hist->id)->exists());

        // Probar relaciones belongsTo inversas
        $this->assertSame($element->id, $reading->hardwareEnergy->id);
        $this->assertSame($element->id, $reading->energy->id);
        $this->assertSame($element->id, $today->hardwareEnergy->id);
        $this->assertSame($element->id, $today->energy->id);
        $this->assertSame($element->id, $hist->hardwareEnergy->id);
        $this->assertSame($element->id, $hist->energy->id);
    }
}
