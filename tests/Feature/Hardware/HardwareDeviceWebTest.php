<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareAvailableComponent;
use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyToday;
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

    #[Test]
    public function show_resolves_device_by_slug(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Custom Slug Device',
            'slug' => 'custom-slug-device',
            'is_public' => true,
        ]);

        $response = $this->get('/hardware/custom-slug-device');

        $response->assertSuccessful();
        $response->assertSee('Custom Slug Device');
    }

    #[Test]
    public function show_returns_404_for_invalid_slug(): void
    {
        $response = $this->get('/hardware/non-existent-device-slug');

        $response->assertNotFound();
    }

    #[Test]
    public function creating_device_without_slug_auto_generates_unique_slug(): void
    {
        $device1 = HardwareDevice::create([
            'name' => 'Raspberry Pi 5',
            'is_public' => true,
        ]);

        $device2 = HardwareDevice::create([
            'name' => 'Raspberry Pi 5',
            'is_public' => true,
        ]);

        $this->assertSame('raspberry-pi-5', $device1->slug);
        $this->assertSame('raspberry-pi-5-2', $device2->slug);
    }

    #[Test]
    public function show_displays_battery_nominal_capacity_with_unit(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Battery Device',
            'is_public' => true,
            'battery_nominal_capacity' => 5000,
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertSee('5,000 mAh');
    }

    #[Test]
    public function show_displays_relative_last_seen_and_recientemente_if_recent(): void
    {
        $recentDevice = HardwareDevice::create([
            'name' => 'Recent Device',
            'is_public' => true,
            'last_seen_at' => Carbon::now()->subMinutes(15),
        ]);

        $olderDevice = HardwareDevice::create([
            'name' => 'Older Device',
            'is_public' => true,
            'last_seen_at' => Carbon::now()->subHours(3),
        ]);

        $responseRecent = $this->get(route('hardware.show', $recentDevice));
        $responseRecent->assertSuccessful();
        $responseRecent->assertSee('Última señal:');
        $responseRecent->assertSee('recientemente');

        $responseOlder = $this->get(route('hardware.show', $olderDevice));
        $responseOlder->assertSuccessful();
        $responseOlder->assertSee('Última señal:');
        $responseOlder->assertDontSee('recientemente');
        $responseOlder->assertSee('hace 3 horas');
    }

    #[Test]
    public function show_indicates_external_link_for_official_manufacturer_url(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Vendor Device',
            'is_public' => true,
            'url_company' => 'https://example.com/product',
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertSee('Web oficial del fabricante');
        $response->assertSee('(sitio externo)');
        $response->assertSee('rel="noopener noreferrer"', escape: false);
    }

    #[Test]
    public function hardware_is_placed_behind_energy_in_navbar_and_footer(): void
    {
        $response = $this->get(route('hardware.index'));

        $response->assertSuccessful();
        $content = $response->getContent();
        $this->assertIsString($content);

        $nav = strstr($content, '<nav', false);
        $this->assertIsString($nav);

        $navbarEnergy = strpos($nav, 'href="'.route('hardware.energy.index').'"');
        $navbarHardware = strpos($nav, 'href="'.route('hardware.index').'"');
        $this->assertNotFalse($navbarEnergy);
        $this->assertNotFalse($navbarHardware);
        $this->assertLessThan($navbarHardware, $navbarEnergy);

        $footer = strstr($content, '<footer', false);
        $this->assertIsString($footer);

        $footerEnergy = strpos($footer, 'href="'.route('hardware.energy.index').'"');
        $footerHardware = strpos($footer, 'href="'.route('hardware.index').'"');
        $this->assertNotFalse($footerEnergy);
        $this->assertNotFalse($footerHardware);
        $this->assertLessThan($footerHardware, $footerEnergy);
    }

    #[Test]
    public function show_displays_7_day_energy_chart_when_device_has_load_and_generator(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Solar Inverter Node',
            'is_public' => true,
        ]);

        $gen = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'is_active' => true,
        ]);

        $load = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'is_active' => true,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $gen->id,
            'date' => Carbon::today()->toDateString(),
            'energy_wh' => 450.0,
            'energy_ah' => 37.5,
            'readings_count' => 10,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $load->id,
            'date' => Carbon::today()->toDateString(),
            'energy_wh' => 200.0,
            'energy_ah' => 16.6,
            'readings_count' => 10,
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertSee('Monitorización de energía (Últimos 7 días)');
        $response->assertSee('Generación Total');
        $response->assertSee('Consumo Total');
        $response->assertSee('Balance Neto');
        $response->assertSee('450');
        $response->assertSee('200');
    }

    #[Test]
    public function show_displays_only_consumption_and_channels_when_device_has_no_generator(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Dual Channel Pi',
            'is_public' => true,
        ]);

        $load0 = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'is_active' => true,
        ]);

        $load1 = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'hardware_device_monitorized_id' => $device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 1,
            'is_active' => true,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $load0->id,
            'date' => Carbon::today()->toDateString(),
            'energy_wh' => 80.0,
            'energy_ah' => 16.0,
            'readings_count' => 5,
        ]);

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $load1->id,
            'date' => Carbon::today()->toDateString(),
            'energy_wh' => 20.0,
            'energy_ah' => 6.0,
            'readings_count' => 5,
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertSee('Monitorización de energía (Últimos 7 días)');
        $response->assertSee('Consumo Total');
        $response->assertSee('Media Diaria');
        $response->assertDontSee('Generación Total');
        $response->assertDontSee('Balance Neto');
        $response->assertSee('Canal 0');
        $response->assertSee('Canal 1');
    }

    #[Test]
    public function show_does_not_display_energy_chart_for_device_without_energy_elements(): void
    {
        $device = HardwareDevice::create([
            'name' => 'Simple Laptop',
            'is_public' => true,
        ]);

        $response = $this->get(route('hardware.show', $device));

        $response->assertSuccessful();
        $response->assertDontSee('Monitorización de energía (Últimos 7 días)');
    }
}
