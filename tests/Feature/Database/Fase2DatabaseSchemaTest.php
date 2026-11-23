<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumProject;
use App\Models\File;
use App\Models\Hardware\HardwareDevice;
use App\Models\Platform;
use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\SmartPlant\SmartPlantRegister;
use App\Models\User;
use App\Services\Platform\PlatformService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class Fase2DatabaseSchemaTest extends ApiTestCase
{
    #[Test]
    public function soft_deleted_records_allow_reusing_unique_slugs_and_emails(): void
    {
        $user = User::factory()->create(['email' => 'reuse@example.test', 'nickname' => 'reusenick']);
        $user->delete();

        $recreatedUser = User::factory()->create(['email' => 'reuse@example.test', 'nickname' => 'reusenick']);
        $this->assertNotSame($user->id, $recreatedUser->id);

        $platform = Platform::create(['user_id' => $recreatedUser->id, 'title' => 'Web', 'slug' => 'same-slug']);
        $platform->delete();
        $recreatedPlatform = Platform::create(['user_id' => $recreatedUser->id, 'title' => 'Web 2', 'slug' => 'same-slug']);
        $this->assertNotSame($platform->id, $recreatedPlatform->id);

        $device = HardwareDevice::create(['user_id' => $recreatedUser->id, 'name' => 'Dev', 'slug' => 'dev-slug']);
        $device->delete();
        $recreatedDevice = HardwareDevice::create(['user_id' => $recreatedUser->id, 'name' => 'Dev 2', 'slug' => 'dev-slug']);
        $this->assertNotSame($device->id, $recreatedDevice->id);

        $cv = Curriculum::create(['user_id' => $recreatedUser->id, 'title' => 'CV', 'slug' => 'cv-slug']);
        $cv->delete();
        $recreatedCv = Curriculum::create(['user_id' => $recreatedUser->id, 'title' => 'CV 2', 'slug' => 'cv-slug']);
        $this->assertNotSame($cv->id, $recreatedCv->id);
    }

    #[Test]
    public function password_resets_table_has_primary_key_id(): void
    {
        $hasPk = DB::selectOne(
            "SELECT 1 AS ok FROM pg_constraint WHERE conrelid = 'password_resets'::regclass AND contype = 'p'"
        );

        $this->assertNotNull($hasPk);
    }

    #[Test]
    public function deleting_a_file_sets_null_on_cv_project_image_id_instead_of_cascading(): void
    {
        $user = User::factory()->create();
        $cv = Curriculum::create(['user_id' => $user->id, 'title' => 'CV Test', 'slug' => 'cv-test']);
        $file = File::create([
            'user_id' => $user->id,
            'name' => 'project.webp',
            'original_name' => 'project.webp',
            'alt' => 'Project',
            'title' => 'Project',
            'path' => 'cv/project.webp',
        ]);

        $project = CurriculumProject::create([
            'curriculum_id' => $cv->id,
            'image_id' => $file->id,
            'title' => 'Proyecto persistente',
            'slug' => 'proyecto-persistente',
        ]);

        $file->delete();

        $project->refresh();
        $this->assertNull($project->image_id);
        $this->assertDatabaseHas('cv_projects', ['id' => $project->id, 'image_id' => null]);
    }

    #[Test]
    public function redundant_two_column_meteorology_indexes_are_removed(): void
    {
        $redundant = DB::select(
            "SELECT indexname FROM pg_indexes WHERE schemaname = 'public' AND tablename IN (
                'meteorology_air_quality','meteorology_eco2','meteorology_humidity',
                'meteorology_light','meteorology_lightning','meteorology_pressure',
                'meteorology_rain','meteorology_temperature','meteorology_tvoc',
                'meteorology_wind_direction','meteorology_winter'
            ) AND indexname LIKE '%_hardware_device_id_created_at_index'"
        );

        $this->assertCount(0, $redundant);
    }

    #[Test]
    public function smartplant_latest_register_eager_loads_without_n_plus_one(): void
    {
        $user = User::factory()->create();
        $plant = SmartPlantPlant::create([
            'user_id' => $user->id,
            'name' => 'Ficus',
            'name_scientific' => 'Ficus benjamina',
            'description' => 'Desc',
            'details' => 'Details',
            'start_at' => now(),
        ]);

        SmartPlantRegister::create([
            'plant_id' => $plant->id,
            'soil_humidity' => 42,
            'created_at' => now()->subHour(),
        ]);
        SmartPlantRegister::create([
            'plant_id' => $plant->id,
            'soil_humidity' => 68,
            'created_at' => now(),
        ]);

        $loaded = SmartPlantPlant::query()->with('latestRegister')->findOrFail($plant->id);

        $this->assertSame(68, (int) $loaded->latestRegister?->soil_humidity);
    }

    #[Test]
    public function platform_service_cache_invalidates_when_platform_changes(): void
    {
        $user = User::factory()->create();
        $platform = Platform::create(['user_id' => $user->id, 'title' => 'Original', 'slug' => 'orig']);

        $service = app(PlatformService::class);
        $this->assertSame('Original', $service->getAll()->firstWhere('id', $platform->id)?->title);

        $platform->update(['title' => 'Actualizada']);
        $this->assertSame('Actualizada', $service->getAll()->firstWhere('id', $platform->id)?->title);
    }

    #[Test]
    public function keycounter_generate_duration_executes_bulk_sql_update_cleanly(): void
    {
        $exitCode = Artisan::call('keycounter:generate_duration');
        $this->assertSame(0, $exitCode);
    }
}
