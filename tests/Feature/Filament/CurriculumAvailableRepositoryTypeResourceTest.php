<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\CurriculumAvailableRepositoryTypeResource;
use App\Filament\Admin\Resources\CV\CurriculumAvailableRepositoryTypes\Pages\ListCurriculumAvailableRepositoryTypes;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\File;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurriculumAvailableRepositoryTypeResourceTest extends TestCase
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
    public function administrator_can_list_repository_types(): void
    {
        $repoType = CurriculumAvailableRepositoryType::create([
            'name' => 'GitHub',
            'title' => 'GitHub Repositories',
            'slug' => 'github',
            'url' => 'https://github.com',
        ]);

        $this->actingAs($this->admin)
            ->get(CurriculumAvailableRepositoryTypeResource::getUrl('index'))
            ->assertOk()
            ->assertSee('GitHub');
    }

    #[Test]
    public function image_column_resolves_image_url_when_file_is_associated(): void
    {
        $file = File::create([
            'module' => 'cv_repository_type',
            'path' => 'cv_repository_type',
            'storage_path' => 'public/cv_repository_type',
            'name' => 'github.webp',
            'original_name' => 'github.webp',
            'width' => 128,
            'height' => 128,
            'size' => 1024,
            'alt' => 'GitHub Logo',
            'title' => 'GitHub Icon',
            'is_private' => false,
        ]);

        $repoType = CurriculumAvailableRepositoryType::create([
            'name' => 'GitHub',
            'title' => 'GitHub',
            'slug' => 'github',
            'image_id' => $file->id,
            'url' => 'https://github.com',
        ]);

        $testable = Livewire::actingAs($this->admin)
            ->test(ListCurriculumAvailableRepositoryTypes::class)
            ->assertCanSeeTableRecords([$repoType]);

        /** @var ListCurriculumAvailableRepositoryTypes $instance */
        $instance = $testable->instance();
        /** @var ImageColumn $column */
        $column = $instance->getTable()->getColumn('image_id');
        $resolvedUrl = $column->record($repoType)->getState();

        $this->assertNotNull($resolvedUrl);
        $this->assertStringContainsString('github.webp', (string) $resolvedUrl);
        $this->assertNotSame((string) $file->id, (string) $resolvedUrl);
    }

    #[Test]
    public function image_column_returns_null_safely_when_repository_type_has_no_image(): void
    {
        $repoType = CurriculumAvailableRepositoryType::create([
            'name' => 'GitLab',
            'title' => 'GitLab',
            'slug' => 'gitlab',
            'image_id' => null,
        ]);

        $testable = Livewire::actingAs($this->admin)
            ->test(ListCurriculumAvailableRepositoryTypes::class)
            ->assertCanSeeTableRecords([$repoType]);

        /** @var ListCurriculumAvailableRepositoryTypes $instance */
        $instance = $testable->instance();
        /** @var ImageColumn $column */
        $column = $instance->getTable()->getColumn('image_id');
        $resolvedUrl = $column->record($repoType)->getState();

        $this->assertNull($resolvedUrl);
    }
}
