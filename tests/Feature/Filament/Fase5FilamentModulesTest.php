<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Pages\AemetDashboard;
use App\Filament\Admin\Pages\SystemOperations;
use App\Filament\Admin\Resources\Files\FileResource;
use App\Filament\Admin\Resources\Newsletters\NewsletterResource;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\SmartPlantPlantResource;
use App\Filament\Admin\Resources\WeatherStation\WeatherStationTelemetryResource;
use App\Models\File;
use App\Models\Hardware\HardwareDevice;
use App\Models\Newsletter;
use App\Models\Platform;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Models\WeatherStation\MeteorologyResumeToday;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verificación funcional de los módulos, páginas y mejoras del panel Filament
 * introducidas en Fase 5.
 */
class Fase5FilamentModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $editor;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->superAdmin = User::factory()->create(['role_id' => UserRoleEnum::SuperAdmin->value, 'is_active' => true]);
        $this->admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->editor = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        $this->user = User::factory()->create(['role_id' => UserRoleEnum::User->value, 'is_active' => true]);
    }

    #[Test]
    public function admin_can_access_newsletter_resource_and_editor_cannot(): void
    {
        $platform = Platform::factory()->create(['user_id' => $this->admin->id]);
        $subscriber = Newsletter::create([
            'platform_id' => $platform->id,
            'email' => 'subscriber@example.com',
            'name' => 'Suscriptor Demo',
            'status' => Newsletter::STATUS_ACTIVE,
            'is_verified' => true,
            'language' => 'es',
        ]);

        $this->actingAs($this->admin)
            ->get(NewsletterResource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee($subscriber->email);

        $this->actingAs($this->editor)
            ->get(NewsletterResource::getUrl('index', panel: 'admin'))
            ->assertForbidden();
    }

    #[Test]
    public function file_resource_isolates_superadmin_files_from_admin(): void
    {
        $superFile = File::create([
            'user_id' => $this->superAdmin->id,
            'module' => 'private_docs',
            'path' => 'private_docs',
            'storage_path' => 'private/private_docs',
            'name' => 'secret-superadmin.pdf',
            'original_name' => 'secret-superadmin.pdf',
            'size' => 1024,
            'alt' => 'secret',
            'title' => 'secret',
            'is_private' => true,
        ]);

        $userFile = File::create([
            'user_id' => $this->user->id,
            'module' => 'uploads',
            'path' => 'uploads',
            'storage_path' => 'public/uploads',
            'name' => 'public-user.webp',
            'original_name' => 'public-user.webp',
            'size' => 2048,
            'alt' => 'public',
            'title' => 'public',
            'is_private' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(FileResource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee($superFile->original_name)
            ->assertSee($userFile->original_name);

        $this->actingAs($this->admin)
            ->get(FileResource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee($userFile->original_name)
            ->assertDontSee($superFile->original_name);

        $this->actingAs($this->editor)
            ->get(FileResource::getUrl('index', panel: 'admin'))
            ->assertForbidden();
    }

    #[Test]
    public function weather_station_telemetry_resource_is_visible_to_admins_only(): void
    {
        $device = HardwareDevice::create([
            'user_id' => $this->admin->id,
            'name' => 'Estación Azotea',
        ]);

        MeteorologyResumeToday::create([
            'hardware_device_id' => $device->id,
            'temperature' => 22.5,
            'humidity' => 61.0,
            'pressure' => 1015.2,
            'wind_speed' => 12.4,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(WeatherStationTelemetryResource::getUrl('index', panel: 'admin'))
            ->assertOk()
            ->assertSee('Estación Azotea');

        $this->actingAs($this->editor)
            ->get(WeatherStationTelemetryResource::getUrl('index', panel: 'admin'))
            ->assertForbidden();
    }

    #[Test]
    public function system_operations_page_is_superadmin_only_and_enforces_whitelist(): void
    {
        $this->actingAs($this->admin)
            ->get(SystemOperations::getUrl(panel: 'admin'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)
            ->get(SystemOperations::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('printers:cleanup-stale-jobs');

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($this->superAdmin)
            ->test(SystemOperations::class)
            ->call('runOperation', 'printers:cleanup-stale-jobs')
            ->assertSet('lastCommand', 'printers:cleanup-stale-jobs')
            ->assertSet('lastExitCode', 0)
            ->call('runOperation', 'migrate:fresh')
            ->assertSet('lastCommand', 'printers:cleanup-stale-jobs');
    }

    #[Test]
    public function tenant_dashboard_renders_overview_widget_with_user_stats(): void
    {
        HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'Sensor Salón',
        ]);

        $this->user->createToken('Token Salón', ['hardware:write']);

        $this->actingAs($this->user)
            ->get('/panel')
            ->assertOk()
            ->assertSee('Tokens activos')
            ->assertSee('Mis dispositivos')
            ->assertSee('Mi cuenta');
    }

    #[Test]
    public function smart_plant_form_uses_spanish_labels_and_aemet_dashboard_uses_panel_css(): void
    {
        $plant = SmartPlantPlant::create([
            'user_id' => $this->superAdmin->id,
            'name' => 'Albahaca',
            'name_scientific' => 'Ocimum basilicum',
            'description' => 'Aromática de maceta',
            'details' => '<p>Riego moderado</p>',
            'start_at' => now(),
        ]);

        $this->actingAs($this->superAdmin)
            ->get(SmartPlantPlantResource::getUrl('edit', ['record' => $plant], panel: 'admin'))
            ->assertOk()
            ->assertSee('Nombre científico')
            ->assertSee('Fecha de siembra')
            ->assertSee('Imagen de la planta');

        $this->actingAs($this->superAdmin)
            ->get(AemetDashboard::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSee('aemet-dash__grid')
            ->assertDontSee('fonts.bunny.net');
    }
}
