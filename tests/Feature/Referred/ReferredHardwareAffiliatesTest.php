<?php

declare(strict_types=1);

namespace Tests\Feature\Referred;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\EditHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers\AffiliateLinksRelationManager;
use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use App\Models\Referred\ReferredPlatform;
use App\Models\Referred\ReferredThing;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de integración del sistema de referidos y afiliados para hardware y componentes.
 */
class ReferredHardwareAffiliatesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    private User $admin;

    private HardwareDevice $device;

    private HardwareComponent $component;

    private ReferredPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->owner = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $this->otherUser = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $this->admin = User::factory()->create(['role_id' => 2, 'is_active' => true]);

        $this->device = HardwareDevice::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Estación Meteorológica DIY',
        ]);

        $this->component = HardwareComponent::factory()->create([
            'hardware_device_id' => $this->device->id,
            'name' => 'Sensor BME280',
        ]);

        $this->platform = ReferredPlatform::factory()->create([
            'name' => 'Amazon',
            'slug' => 'amazon',
        ]);
    }

    #[Test]
    public function can_create_affiliate_link_for_entire_hardware_device(): void
    {
        $link = ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => null,
            'name' => 'Pack Raspberry Pi 5 con fuente',
            'url' => 'https://amzn.to/example1',
            'price' => 85.50,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $this->assertTrue($this->device->affiliateLinks->contains('id', $link->id));
        $this->assertTrue($this->device->deviceAffiliateLinks->contains('id', $link->id));
        $this->assertSame('Dispositivo completo', $link->target_label);
        $this->assertSame('85,50 EUR', $link->formatted_price);
    }

    #[Test]
    public function can_create_affiliate_link_for_specific_hardware_component(): void
    {
        $link = ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => $this->component->id,
            'name' => 'Sensor BME280 original Bosch',
            'url' => 'https://amzn.to/sensor-bme280',
            'price' => 12.99,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $this->assertTrue($this->device->affiliateLinks->contains('id', $link->id));
        $this->assertFalse($this->device->deviceAffiliateLinks->contains('id', $link->id));
        $this->assertTrue($this->component->affiliateLinks->contains('id', $link->id));
        $this->assertSame('Sensor BME280', $link->target_label);
    }

    #[Test]
    public function deleting_component_cascades_and_deletes_its_affiliate_links(): void
    {
        $link = ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => $this->component->id,
            'url' => 'https://amzn.to/component-link',
        ]);

        $this->assertDatabaseHas('referred_things', ['id' => $link->id]);

        $this->component->forceDelete();

        $this->assertDatabaseMissing('referred_things', ['id' => $link->id]);
    }

    #[Test]
    public function deleting_hardware_device_cascades_and_deletes_all_its_affiliate_links(): void
    {
        $deviceLink = ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => null,
            'url' => 'https://amzn.to/device-link',
        ]);

        $componentLink = ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => $this->component->id,
            'url' => 'https://amzn.to/component-link-2',
        ]);

        $this->device->forceDelete();

        $this->assertDatabaseMissing('referred_things', ['id' => $deviceLink->id]);
        $this->assertDatabaseMissing('referred_things', ['id' => $componentLink->id]);
    }

    #[Test]
    public function user_cannot_update_affiliate_link_of_another_users_hardware_unless_admin(): void
    {
        $link = ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => null,
            'url' => 'https://amzn.to/device-link',
        ]);

        $this->assertTrue(Gate::forUser($this->owner)->allows('update', $link));
        $this->assertFalse(Gate::forUser($this->otherUser)->allows('update', $link));
        $this->assertTrue(Gate::forUser($this->admin)->allows('update', $link));
    }

    #[Test]
    public function filament_relation_manager_renders_and_creates_affiliate_links(): void
    {
        $superAdmin = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($superAdmin);

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        ReferredThing::create([
            'referred_platform_id' => $this->platform->id,
            'hardware_device_id' => $this->device->id,
            'hardware_component_id' => null,
            'name' => 'Placa base oficial',
            'url' => 'https://amzn.to/test-filament',
            'price' => 55.00,
            'is_active' => true,
        ]);

        Livewire::test(AffiliateLinksRelationManager::class, [
            'ownerRecord' => $this->device,
            'pageClass' => EditHardwareDevice::class,
        ])
            ->assertSuccessful()
            ->assertSee('Placa base oficial')
            ->assertSee('Amazon')
            ->assertSee('Dispositivo completo');
    }

    #[Test]
    public function admin_can_access_referred_platforms_index(): void
    {
        $this->actingAs($this->admin);

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get(ReferredPlatformResource::getUrl('index', panel: 'admin'))
            ->assertSuccessful();
    }

    #[Test]
    public function regular_user_cannot_access_referred_platforms_index(): void
    {
        $this->actingAs($this->owner);

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->get(ReferredPlatformResource::getUrl('index', panel: 'admin'))
            ->assertForbidden();
    }
}
