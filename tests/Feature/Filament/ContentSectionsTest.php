<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\CreateContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContentSeo;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContentTaxonomies;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContentVisibility;
use App\Filament\Admin\Resources\Content\Contents\Pages\ListContents;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentSeo;
use App\Models\Content\ContentTag;
use App\Models\File;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * La ficha del contenido por secciones (E2–E7, G3 y C7 de la auditoría de
 * contenidos; F7 del plan del 2026-09-24).
 */
class ContentSectionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private const SECTIONS = ['edit', 'pages', 'seo', 'taxonomies', 'relations', 'visibility', 'preview'];

    private User $admin;

    private User $contributor;

    private User $outsider;

    private Platform $platform;

    private Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        $this->useTemporaryStorage();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $editor = fn (): User => User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        [$this->contributor, $this->outsider] = [$editor(), $editor()];

        $this->platform = Platform::factory()->create();
        $this->content = Content::factory()->draft()->create(['platform_id' => $this->platform->id, 'author_id' => $this->admin->id, 'title' => 'Estación meteorológica']);
        app(ContentContributorService::class)->add($this->content, $this->contributor);

        $this->actingAs($this->admin);
    }

    #[Test]
    public function every_section_opens_for_who_can_edit_and_not_for_anyone_else(): void
    {
        foreach (self::SECTIONS as $section) {
            $url = ContentResource::getUrl($section, ['record' => $this->content]);

            $this->actingAs($this->contributor)->get($url)->assertOk();
            // Como la ficha: el listado de un Editor ni siquiera lo encuentra.
            $this->actingAs($this->outsider)->get($url)->assertNotFound();
        }
    }

    #[Test]
    public function the_record_navigation_has_the_six_sections_at_the_top(): void
    {
        $html = $this->get(ContentResource::getUrl('seo', ['record' => $this->content]))->assertOk()->getContent();

        foreach (['Datos', 'Páginas', 'SEO', 'Categorías y etiquetas', 'Relacionados', 'Visibilidad'] as $label) {
            $this->assertStringContainsString($label, (string) $html);
        }

        $this->assertStringContainsString('Vista previa', (string) $html);
    }

    #[Test]
    public function the_seo_section_saves_its_fields_and_the_social_image_as_webp(): void
    {
        Livewire::test(EditContentSeo::class, ['record' => $this->content->getRouteKey()])
            ->assertFormSet(['og_type' => 'article', 'distribution' => 'global', 'robots' => 'index, follow'])
            ->fillForm([
                // Más de 160: se avisa, no se impide.
                'description' => str_repeat('Descripción larga. ', 10),
                'keywords' => 'meteorología, raspberry',
                'robots' => 'noindex, follow',
                'og_title' => 'Mi estación',
                'og_type' => 'article',
                'twitter_card' => 'summary_large_image',
                'twitter_creator' => 'raupulus',
                'image_id' => [UploadedFile::fake()->image('social.jpg', 1200, 630)],
                'image_alt' => 'La estación en el tejado',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $seo = ContentSeo::query()->where('content_id', $this->content->id)->sole();
        $this->assertSame('noindex, follow', $seo->robots);
        $this->assertSame('summary_large_image', $seo->twitter_card);
        $this->assertSame('La estación en el tejado', $seo->image_alt);
        $this->assertSame('image/webp', File::query()->findOrFail($seo->image_id)->fileType?->mime);

        // El contenido no se toca.
        $this->assertSame('Estación meteorológica', $this->content->refresh()->title);
    }

    #[Test]
    public function the_taxonomies_section_saves_the_platform_ones_with_their_main_category(): void
    {
        [$weather, $diy, $foreign] = [$this->category('Meteorología'), $this->category('Hazlo tú'), $this->category('De otra web', linked: false)];
        $tag = Tag::query()->create(['name' => 'Raspberry', 'slug' => 'raspberry']);
        PlatformTag::query()->create(['platform_id' => $this->platform->id, 'tag_id' => $tag->id]);

        Livewire::test(EditContentTaxonomies::class, ['record' => $this->content->getRouteKey()])
            ->assertFormFieldExists('categories')
            ->fillForm([
                'categories' => [$weather->id, $diy->id],
                'main_category_id' => $weather->id,
                'tags' => [$tag->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $rows = ContentCategory::query()->where('content_id', $this->content->id)->with('platformCategory')->get();
        $this->assertEqualsCanonicalizing([$weather->id, $diy->id], $rows->pluck('platformCategory.category_id')->all());
        $this->assertSame([$weather->id], $rows->where('is_main', true)->pluck('platformCategory.category_id')->values()->all());
        $this->assertSame(1, ContentTag::query()->where('content_id', $this->content->id)->count());
        $this->assertNotContains($foreign->id, $rows->pluck('platformCategory.category_id')->all());
    }

    #[Test]
    public function a_new_category_or_tag_can_be_created_without_leaving_the_section(): void
    {
        Livewire::test(EditContentTaxonomies::class, ['record' => $this->content->getRouteKey()])
            ->callAction(TestAction::make('createOption')->schemaComponent('categories'), ['name' => 'Electrónica'])
            ->callAction(TestAction::make('createOption')->schemaComponent('tags'), ['name' => 'Sensores'])
            ->call('save')
            ->assertHasNoFormErrors();

        $category = Category::query()->where('name', 'Electrónica')->sole();
        $this->assertTrue(PlatformCategory::query()->where('platform_id', $this->platform->id)->where('category_id', $category->id)->exists());
        $this->assertSame(['Electrónica'], $this->content->refresh()->categoriesQuery()->pluck('name')->all());
        $this->assertSame(['Sensores'], $this->content->tagsQuery()->pluck('name')->all());
    }

    #[Test]
    public function the_visibility_section_saves_the_switches_and_keeps_an_unchecked_copyright_empty(): void
    {
        $this->content->update(['is_copyright_valid' => null]);

        Livewire::test(EditContentVisibility::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['is_visible_on_home' => true, 'is_comment_enabled' => true, 'is_comment_anonymous' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->content->refresh();
        $this->assertTrue($this->content->is_visible_on_home);
        $this->assertTrue($this->content->is_comment_enabled);
        $this->assertNull($this->content->is_copyright_valid, '«Sin comprobar» no pasa a «no» al guardar.');
    }

    #[Test]
    public function a_slug_is_unique_per_platform_and_says_who_has_it_in_the_trash(): void
    {
        $trashed = Content::factory()->create(['platform_id' => $this->platform->id, 'slug' => 'ocupado', 'title' => 'El de la papelera']);
        $trashed->delete();
        $type = (int) $this->content->type_id;

        Livewire::test(CreateContent::class)
            ->fillForm(['title' => 'Nuevo', 'slug' => 'ocupado', 'platform_id' => $this->platform->id, 'type_id' => $type, 'status_id' => 1])
            ->call('create')
            ->assertHasFormErrors(['slug'])
            ->assertSee('Ese slug lo tiene «El de la papelera», que está en la papelera');

        // En otra plataforma, el mismo slug vale (antes se pedía único en todas).
        Livewire::test(CreateContent::class)
            ->fillForm(['title' => 'Nuevo', 'slug' => 'ocupado', 'platform_id' => Platform::factory()->create()->id, 'type_id' => $type, 'status_id' => 1])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    #[Test]
    public function the_trash_restores_and_only_the_superadmin_deletes_for_good(): void
    {
        $list = fn () => Livewire::test(ListContents::class)->filterTable('trashed', false);

        Livewire::test(ListContents::class)->callAction(TestAction::make('delete')->table($this->content));
        $this->assertSoftDeleted($this->content);

        $list()
            ->assertCanSeeTableRecords([$this->content])
            ->assertActionHidden(TestAction::make('forceDelete')->table($this->content))
            ->callAction(TestAction::make('restore')->table($this->content));
        $this->assertNotSoftDeleted($this->content);

        $this->content->delete();
        $this->actingAs(User::factory()->create(['role_id' => UserRoleEnum::SuperAdmin->value, 'is_active' => true]));
        $list()->callAction(TestAction::make('forceDelete')->table($this->content));
        $this->assertModelMissing($this->content);
    }

    #[Test]
    public function a_page_goes_to_the_trash_and_comes_back_at_the_end(): void
    {
        $first = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Uno', 'slug' => 'uno', 'order' => 1]);
        $second = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Dos', 'slug' => 'dos', 'order' => 2]);

        Livewire::test(ManageContentPages::class, ['record' => $this->content->getRouteKey(), 'page' => $first->id])
            ->callAction('deletePage')
            ->assertRedirect(ContentResource::getUrl('pages', ['record' => $this->content, 'page' => $second->id]));

        $this->assertSoftDeleted($first);
        $this->assertSame(1, $second->refresh()->order, 'Las de detrás suben un puesto.');

        Livewire::test(ManageContentPages::class, ['record' => $this->content->getRouteKey(), 'page' => $second->id])
            ->assertActionHidden(TestAction::make('forceDeletePage')->arguments(['page' => $first->id]))
            ->callAction(TestAction::make('restorePage')->arguments(['page' => $first->id]));

        $this->assertNotSoftDeleted($first);
        $this->assertSame(2, $first->refresh()->order);
    }

    #[Test]
    public function the_preview_shows_every_page_in_order_with_the_served_html(): void
    {
        ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Segunda', 'slug' => 'segunda', 'order' => 2, 'content' => '<table class="r-table"><tr><td>Celda</td></tr></table>']);
        ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Primera', 'slug' => 'primera', 'order' => 1, 'content' => '<p class="r-paragraph">Hola <b>mundo</b></p>']);

        $this->get(ContentResource::getUrl('preview', ['record' => $this->content]))
            ->assertOk()
            ->assertSeeInOrder(['Página 1 · Primera', 'Hola <b>mundo</b>', 'Página 2 · Segunda', '<td>Celda</td>'], escape: false);
    }

    private function category(string $name, bool $linked = true): Category
    {
        $category = Category::query()->create(['name' => $name, 'slug' => str($name)->slug()->toString()]);

        if ($linked) {
            PlatformCategory::query()->create(['platform_id' => $this->platform->id, 'category_id' => $category->id]);
        }

        return $category;
    }
}
