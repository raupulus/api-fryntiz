<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Platforms\Pages\EditPlatform;
use App\Filament\Admin\Resources\Platforms\PlatformResource;
use App\Filament\Admin\Resources\Platforms\RelationManagers\CategoriesRelationManager;
use App\Models\Category;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las categorías de una plataforma se ven y se gestionan desde la propia
 * plataforma (`platform_categories`).
 */
class PlatformCategoriesRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    private Platform $platform;

    private Category $electronics;

    private Category $linux;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $admin = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($admin);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->platform = Platform::factory()->create(['user_id' => $admin->id]);
        $this->electronics = Category::query()->create(['name' => 'Electrónica', 'slug' => 'electronica', 'priority' => 0]);
        $this->linux = Category::query()->create(['name' => 'Linux', 'slug' => 'linux', 'priority' => 0]);
        PlatformCategory::query()->create(['platform_id' => $this->platform->id, 'category_id' => $this->electronics->id]);
    }

    private function manager()
    {
        return Livewire::test(CategoriesRelationManager::class, ['ownerRecord' => $this->platform, 'pageClass' => EditPlatform::class]);
    }

    #[Test]
    public function it_lists_the_categories_of_the_platform_and_only_those(): void
    {
        $row = PlatformCategory::query()->where('platform_id', $this->platform->id)->first();
        $other = PlatformCategory::query()->create(['platform_id' => Platform::factory()->create()->id, 'category_id' => $this->linux->id]);

        $this->manager()->assertCanSeeTableRecords([$row])->assertCanNotSeeTableRecords([$other]);
    }

    #[Test]
    public function it_is_registered_in_the_platform_resource(): void
    {
        $this->assertContains(CategoriesRelationManager::class, PlatformResource::getRelations());
    }

    #[Test]
    public function it_adds_a_category_to_the_platform_and_keeps_the_ones_it_had(): void
    {
        $this->manager()
            ->callAction(TestAction::make('attach')->table(), ['categories' => [$this->linux->id]])
            ->assertHasNoActionErrors();

        $this->assertSame(2, PlatformCategory::query()->where('platform_id', $this->platform->id)->count());
    }

    #[Test]
    public function it_only_offers_the_categories_the_platform_does_not_have(): void
    {
        $this->manager()
            ->mountAction(TestAction::make('attach')->table())
            ->assertSchemaComponentExists(
                'categories',
                'mountedActionSchema0',
                fn (Select $field): bool => array_keys($field->getOptions()) === [$this->linux->id],
            );
    }

    #[Test]
    public function it_removes_a_category_from_the_platform_without_deleting_the_category(): void
    {
        $row = PlatformCategory::query()->where('platform_id', $this->platform->id)->first();

        $this->manager()->callAction(TestAction::make('delete')->table($row))->assertHasNoActionErrors();

        $this->assertModelMissing($row);
        $this->assertModelExists($this->electronics);
    }
}
