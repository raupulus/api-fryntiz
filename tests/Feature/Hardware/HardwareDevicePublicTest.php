<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\HardwareDeviceResource;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HardwareDevicePublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    #[Test]
    public function is_public_defaults_to_false(): void
    {
        $device = HardwareDevice::create([
            'name' => 'ESP32 Test',
        ]);

        $this->assertFalse($device->is_public);
        $this->assertDatabaseHas('hardware_devices', [
            'id' => $device->id,
            'is_public' => false,
        ]);
    }

    #[Test]
    public function can_mark_and_unmark_is_public(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Raspberry Pi',
            'is_public' => false,
        ]);

        $device->update(['is_public' => true]);
        $this->assertTrue($device->fresh()->is_public);

        $device->update(['is_public' => false]);
        $this->assertFalse($device->fresh()->is_public);
    }

    #[Test]
    public function scope_public_filters_only_public_devices(): void
    {
        $public = HardwareDevice::create(['name' => 'Public Device', 'is_public' => true]);
        HardwareDevice::create(['name' => 'Private Device', 'is_public' => false]);

        $results = HardwareDevice::public()->get();

        $this->assertCount(1, $results);
        $this->assertSame($public->id, $results->first()->id);
    }

    #[Test]
    public function factory_respects_public_state(): void
    {
        $defaultDevice = HardwareDevice::factory()->create();
        $this->assertFalse($defaultDevice->is_public);

        $publicDevice = HardwareDevice::factory()->public()->create();
        $this->assertTrue($publicDevice->is_public);
    }

    #[Test]
    public function admin_can_see_hardware_device_resource_with_is_public(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);

        $device = HardwareDevice::create([
            'name' => 'Router Lab',
            'user_id' => $admin->id,
            'is_public' => true,
        ]);

        $this->actingAs($admin);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get(HardwareDeviceResource::getUrl('index', panel: 'admin'))
            ->assertSuccessful();

        $this->get(HardwareDeviceResource::getUrl('edit', ['record' => $device], panel: 'admin'))
            ->assertSuccessful();
    }
}
