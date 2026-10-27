<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Referred\ReferredPlatforms\Pages\ListReferredPlatforms;
use App\Filament\Admin\Resources\Referred\ReferredPlatforms\ReferredPlatformResource;
use App\Models\File;
use App\Models\Referred\ReferredPlatform;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReferredPlatformResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function administrator_can_list_referred_platforms(): void
    {
        $platform = ReferredPlatform::create([
            'name' => 'Amazon Associates',
            'slug' => 'amazon-associates',
            'url' => 'https://amazon.es',
        ]);

        $this->actingAs($this->admin)
            ->get(ReferredPlatformResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Amazon Associates');
    }

    #[Test]
    public function logo_column_resolves_image_url_when_file_is_associated(): void
    {
        $file = File::create([
            'module' => 'referred_platforms',
            'path' => 'referred_platforms',
            'storage_path' => 'public/referred_platforms',
            'name' => 'amazon.webp',
            'original_name' => 'amazon.webp',
            'width' => 128,
            'height' => 128,
            'size' => 1024,
            'alt' => 'Amazon Logo',
            'title' => 'Logo Amazon',
            'is_private' => false,
        ]);

        $platform = ReferredPlatform::create([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'image_id' => $file->id,
            'url' => 'https://amazon.es',
        ]);

        $testable = Livewire::actingAs($this->admin)
            ->test(ListReferredPlatforms::class)
            ->assertCanSeeTableRecords([$platform]);

        /** @var ListReferredPlatforms $instance */
        $instance = $testable->instance();
        /** @var ImageColumn $column */
        $column = $instance->getTable()->getColumn('image_id');
        $resolvedUrl = $column->record($platform)->getState();

        $this->assertNotNull($resolvedUrl);
        $this->assertStringContainsString('amazon.webp', (string) $resolvedUrl);
        $this->assertNotSame((string) $file->id, (string) $resolvedUrl);
    }

    #[Test]
    public function logo_column_returns_null_safely_when_platform_has_no_image(): void
    {
        $platform = ReferredPlatform::create([
            'name' => 'Sin Logo',
            'slug' => 'sin-logo',
            'image_id' => null,
        ]);

        $testable = Livewire::actingAs($this->admin)
            ->test(ListReferredPlatforms::class)
            ->assertCanSeeTableRecords([$platform]);

        /** @var ListReferredPlatforms $instance */
        $instance = $testable->instance();
        /** @var ImageColumn $column */
        $column = $instance->getTable()->getColumn('image_id');
        $resolvedUrl = $column->record($platform)->getState();

        $this->assertNull($resolvedUrl);
    }
}
