<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContentTaxonomies;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Cambiar la plataforma de un contenido no le hace perder las categorías ni
 * las etiquetas, y la sección «Categorías y etiquetas» sigue guardando
 * (auditoría del editor del 2026-10-14).
 *
 * Van ligadas a la plataforma: antes, al cambiarla, dejaban de verse en el
 * panel y en la API, y la categoría principal de la anterior se cargaba en el
 * formulario sin estar entre las opciones e impedía guardar.
 */
class ContentPlatformChangeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private Platform $from;

    private Platform $to;

    private Content $content;

    private Category $shared;

    private Category $onlyFrom;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->actingAs($admin);

        [$this->from, $this->to] = [Platform::factory()->create(), Platform::factory()->create()];
        $this->shared = Category::query()->create(['name' => 'Linux', 'slug' => 'linux']);
        $this->onlyFrom = Category::query()->create(['name' => 'Electrónica', 'slug' => 'electronica']);
        $this->tag = Tag::query()->create(['name' => 'Debian', 'slug' => 'debian']);

        foreach ([$this->shared, $this->onlyFrom] as $category) {
            PlatformCategory::query()->create(['platform_id' => $this->from->id, 'category_id' => $category->id]);
        }
        PlatformCategory::query()->create(['platform_id' => $this->to->id, 'category_id' => $this->shared->id]);
        PlatformTag::query()->create(['platform_id' => $this->from->id, 'tag_id' => $this->tag->id]);

        $this->content = Content::factory()->draft()->create(['platform_id' => $this->from->id, 'author_id' => $admin->id]);
        $this->content->saveCategories([$this->shared->id, $this->onlyFrom->id], [], $this->shared->id);
        $this->content->saveTags([$this->tag->id]);
    }

    #[Test]
    public function the_tags_and_the_categories_the_new_platform_has_move_with_the_content(): void
    {
        $this->content->update(['platform_id' => $this->to->id]);
        $this->content->refresh();

        $this->assertSame(['Linux'], $this->content->categoriesQuery()->pluck('name')->all());
        $this->assertSame(['Debian'], $this->content->tagsQuery()->pluck('name')->all());
        $this->assertSame([$this->shared->id], ContentCategory::query()->where('content_id', $this->content->id)->where('is_main', true)
            ->with('platformCategory')->get()->pluck('platformCategory.category_id')->all(), 'Sigue siendo la principal.');

        // La que la nueva no tiene se queda en la anterior: vuelve si vuelve.
        $this->content->update(['platform_id' => $this->from->id]);
        $this->assertEqualsCanonicalizing(['Linux', 'Electrónica'], $this->content->refresh()->categoriesQuery()->pluck('name')->all());
        $this->assertFalse(PlatformCategory::query()->where('platform_id', $this->to->id)->where('category_id', $this->onlyFrom->id)->exists(), 'No se añade al menú de la nueva.');
    }

    #[Test]
    public function changing_the_platform_in_the_panel_says_what_happened(): void
    {
        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['platform_id' => $this->to->id])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Plataforma cambiada');

        $this->assertNull($this->content->refresh()->taxonomyMove, 'Es del guardado, no se queda en el modelo.');
    }

    #[Test]
    public function the_taxonomies_section_still_saves_after_the_change(): void
    {
        // La principal se queda en la anterior: no se carga en el formulario.
        $this->content->saveCategories([$this->onlyFrom->id], [], $this->onlyFrom->id);
        $this->content->update(['platform_id' => $this->to->id]);

        Livewire::test(EditContentTaxonomies::class, ['record' => $this->content->getRouteKey()])
            ->assertFormSet(['main_category_id' => null])
            ->fillForm(['categories' => [$this->shared->id], 'main_category_id' => $this->shared->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Linux'], $this->content->refresh()->categoriesQuery()->pluck('name')->all());
    }

    #[Test]
    public function removing_a_category_removes_its_subcategories_from_the_form(): void
    {
        $child = Category::query()->create(['name' => 'Raspberry', 'slug' => 'raspberry', 'parent_id' => $this->shared->id]);
        PlatformCategory::query()->create(['platform_id' => $this->from->id, 'category_id' => $child->id]);

        Livewire::test(EditContentTaxonomies::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['categories' => [$this->shared->id], 'subcategories' => [$child->id]])
            ->fillForm(['categories' => [$this->onlyFrom->id]])
            ->assertFormSet(['subcategories' => []])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
