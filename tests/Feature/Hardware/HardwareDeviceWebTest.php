<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareAvailableComponent;
use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HardwareDeviceWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    #[Test]
    public function index_displays_public_hardware_devices(): void
    {
        $type = HardwareType::create([
            'name' => 'Micro PC',
            'slug' => 'micro-pc',
        ]);

        $publicDevice = HardwareDevice::create([
            'name' => 'Raspberry Pi Public',
            'hardware_type_id' => $type->id,
            'is_public' => true,
            'temp' => 45.5,
            'cpu' => 12.0,
            'ram' => 55.0,
            'last_seen_at' => Carbon::now()->subMinutes(10),
        ]);

        $privateDevice = HardwareDevice::create([
            'name' => 'Raspberry Pi Secret',
            'hardware_type_id' => $type->id,
            'is_public' => false,
        ]);

        $response = $this->get(route('hardware.index'));

        $response->assertSuccessful();
        $response->assertSee('Raspberry Pi Public');
        $response->assertDontSee('Raspberry Pi Secret');
    }

    #[Test]
    public function index_strictly_enforces_privacy_by_design(): void
    {
        $user = User::factory()->create();
        $privateIp = '192.168.1.189';
        $publicIp = '85.55.120.44';
        $serialNumber = 'SN-CONFIDENTIAL-9988';

        HardwareDevice::create([
            'name' => 'Exposed Device',
            'is_public' => true,
            'ip_local' => $privateIp,
            'ip_public' => $publicIp,
            'serial_number' => $serialNumber,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('hardware.index'));

        $response->assertSuccessful();
        $response->assertSee('Exposed Device');
        $response->assertDontSee($privateIp);
        $response->assertDontSee($publicIp);
        $response->assertDontSee($serialNumber);
    }

    #[Test]
    public function show_displays_public_device_details_and_components(): void
    {
        $device = HardwareDevice::create([
            'name' => 'ESP32 Node',
            'brand' => 'Espressif',
            'model' => 'ESP32-WROOM-32',
            'software_version' => '1.4.0',
            'is_public' => true,
            'temp' => 38.2,
            'cpu' => 5.0,
            'ram' => 40.0,
            'uptime' => 172800, // 2 días
            'last_seen_at' => Carbon::now()->subMinutes(5),
        ]);

        $availableComp = HardwareAvailableComponent::create([
            'name' => 'Sensor Barométrico',
            'type' => 'sensor',
            'slug' => 'sensor-barometrico',
        ]);

        HardwareComponent::create([
            'hardware_device_id' => $device->id,
            'hardware_available_component_id' => $availableComp->id,
            'name' => 'BME280 Temp/Hum/Pres',
            'brand' => 'Bosch',
            'model' => 'BME280',
            'quantity' => '1',
            'power' => '0.05',
            'description' => 'Sensor ambiental I2C',
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertSee('ESP32 Node');
        $response->assertSee('Espressif');
        $response->assertSee('ESP32-WROOM-32');
        $response->assertSee('v1.4.0');
        $response->assertSee('BME280 Temp/Hum/Pres');
        $response->assertSee('Bosch');
        $response->assertSee('En línea');
    }

    #[Test]
    public function show_returns_404_for_private_devices(): void
    {
        $privateDevice = HardwareDevice::create([
            'name' => 'Hidden Server',
            'is_public' => false,
        ]);

        $response = $this->get(route('hardware.show', $privateDevice));

        $response->assertNotFound();
    }

    #[Test]
    public function show_strictly_enforces_privacy_by_design(): void
    {
        $user = User::factory()->create();
        $privateIp = '10.0.0.123';
        $publicIp = '92.12.34.56';
        $serialNumber = 'SEC-SN-77665544';

        $device = HardwareDevice::create([
            'name' => 'Secure Node',
            'is_public' => true,
            'ip_local' => $privateIp,
            'ip_public' => $publicIp,
            'serial_number' => $serialNumber,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertSee('Secure Node');
        $response->assertDontSee($privateIp);
        $response->assertDontSee($publicIp);
        $response->assertDontSee($serialNumber);
    }

    #[Test]
    public function sitemap_command_includes_hardware_routes(): void
    {
        $publicDevice = HardwareDevice::create([
            'name' => 'Sitemap Public Node',
            'is_public' => true,
        ]);

        $privateDevice = HardwareDevice::create([
            'name' => 'Sitemap Private Node',
            'is_public' => false,
        ]);

        $this->artisan('sitemap:generate', ['--force' => true])
            ->assertSuccessful();

        $sitemapPath = public_path('sitemap.xml');
        $this->assertFileExists($sitemapPath);

        $content = file_get_contents($sitemapPath);
        $this->assertIsString($content);
        $this->assertStringContainsString(route('hardware.index'), $content);
        $this->assertStringContainsString(route('hardware.show', $publicDevice), $content);
        $this->assertStringNotContainsString(route('hardware.show', $privateDevice), $content);
    }
}
