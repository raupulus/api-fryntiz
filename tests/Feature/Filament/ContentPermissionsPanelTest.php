<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ContentStatusEnum as Status;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\CreateContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\ListContents;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\ContributorsRelationManager;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\RelatedRelationManager;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use App\Models\PlatformUser;
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
 * Los permisos de F5 aplicados en el panel (plan de contenidos del
 * 2026-09-24). Son las pruebas de la auditoría repetidas: lista, «Publicar»
 * masivo, crear en otra plataforma y cambiar el autor, que antes le salían a un
 * Editor y ahora no.
 */
class ContentPermissionsPanelTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private Platform $platform;

    private User $author;

    private User $contributor;

    private User $outsider;

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

        $this->platform = Platform::factory()->create();
        $editor = fn (): User => User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        [$this->author, $this->contributor, $this->outsider] = [$editor(), $editor(), $editor()];

        foreach ([$this->author, $this->outsider] as $user) {
            $user->platforms()->attach($this->platform->id);
        }

        $this->content = Content::factory()->draft()->create(['platform_id' => $this->platform->id, 'author_id' => $this->author->id]);
        app(ContentContributorService::class)->add($this->content, $this->contributor);
    }

    #[Test]
    public function an_editor_only_sees_the_contents_where_they_author_or_contribute(): void
    {
        $foreign = Content::factory()->create(['platform_id' => $this->platform->id]);

        $this->actingAs($this->contributor);
        Livewire::test(ListContents::class)->assertCanSeeTableRecords([$this->content])->assertCanNotSeeTableRecords([$foreign]);

        // Tener la plataforma no basta para ver lo de otros.
        $this->actingAs($this->outsider);
        Livewire::test(ListContents::class)->assertCanNotSeeTableRecords([$this->content, $foreign]);
    }

    /**
     * Ni siquiera sabe que existe: la ficha busca dentro de lo que el Editor
     * alcanza (`ContentResource::getEloquentQuery()`), y responde 404.
     */
    #[Test]
    public function an_editor_cannot_open_an_unrelated_content(): void
    {
        $this->actingAs($this->outsider);

        $this->get(EditContent::getUrl(['record' => $this->content]))->assertNotFound();
    }

    #[Test]
    public function the_bulk_publish_skips_what_the_user_cannot_publish(): void
    {
        $this->actingAs($this->contributor);

        Livewire::test(ListContents::class)
            ->selectTableRecords([$this->content->getKey()])
            ->callAction(TestAction::make('publish')->table()->bulk());

        $this->assertSame(Status::Draft->value, $this->content->refresh()->status_id);

        $this->actingAs($this->author);

        Livewire::test(ListContents::class)
            ->selectTableRecords([$this->content->getKey()])
            ->callAction(TestAction::make('publish')->table()->bulk());

        $this->assertSame(Status::Published->value, $this->content->refresh()->status_id);
    }

    #[Test]
    public function the_bulk_delete_skips_what_the_user_cannot_delete(): void
    {
        $this->actingAs($this->contributor);

        Livewire::test(ListContents::class)
            ->selectTableRecords([$this->content->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertNotSoftDeleted($this->content);
    }

    #[Test]
    public function an_editor_cannot_create_in_a_platform_that_is_not_theirs_and_is_always_the_author(): void
    {
        $this->actingAs($this->author);
        $other = Platform::factory()->create();
        $type = $this->content->type_id;

        Livewire::test(CreateContent::class)
            ->fillForm(['title' => 'Otra web', 'slug' => 'otra-web', 'platform_id' => $other->id, 'type_id' => $type, 'status_id' => Status::Draft->value])
            ->call('create')
            ->assertHasFormErrors(['platform_id']);

        Livewire::test(CreateContent::class)
            ->fillForm(['title' => 'Mi web', 'slug' => 'mi-web', 'platform_id' => $this->platform->id, 'type_id' => $type, 'status_id' => Status::Draft->value, 'author_id' => $this->outsider->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($this->author->id, Content::query()->where('slug', 'mi-web')->value('author_id'));
    }

    #[Test]
    public function a_contributor_cannot_change_author_or_platform_nor_publish(): void
    {
        $this->actingAs($this->contributor);

        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->assertFormFieldDisabled('author_id')
            ->assertFormFieldDisabled('platform_id')
            ->fillForm(['status_id' => Status::Published->value])
            ->call('save')
            ->assertHasFormErrors(['status_id']);

        $this->assertSame(Status::Draft->value, $this->content->refresh()->status_id);
    }

    #[Test]
    public function a_contributor_schedules_with_at_least_a_week(): void
    {
        $this->actingAs($this->contributor);

        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['status_id' => Status::Scheduled->value, 'scheduled_at' => now()->addDays(2)])
            ->call('save')
            ->assertHasFormErrors(['scheduled_at']);

        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['status_id' => Status::Scheduled->value, 'scheduled_at' => now()->addDays(8)])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(Status::Scheduled->value, $this->content->refresh()->status_id);
    }

    #[Test]
    public function only_the_author_manages_contributors_and_editors_do_not_see_emails(): void
    {
        $newcomer = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true, 'name' => 'Nuevo Colaborador']);
        $manager = fn () => Livewire::test(ContributorsRelationManager::class, ['ownerRecord' => $this->content, 'pageClass' => EditContent::class]);

        $this->actingAs($this->contributor);
        $manager()
            ->assertActionHidden(TestAction::make('addContributor')->table())
            ->assertTableColumnHidden('email');

        $this->actingAs($this->author);
        $manager()
            ->callAction(TestAction::make('addContributor')->table(), ['user_id' => $newcomer->id])
            ->assertHasNoErrors();

        $this->assertTrue($this->content->refresh()->hasContributor($newcomer));
    }

    #[Test]
    public function the_related_selector_only_offers_reachable_contents_from_two_letters(): void
    {
        $reachable = Content::factory()->create(['platform_id' => $this->platform->id, 'author_id' => $this->author->id, 'title' => 'Estación meteorológica']);
        Content::factory()->create(['platform_id' => $this->platform->id, 'title' => 'Estación ajena']);

        $this->actingAs($this->author);
        $manager = Livewire::test(RelatedRelationManager::class, ['ownerRecord' => $this->content, 'pageClass' => EditContent::class])->instance();
        $search = fn (string $text): array => (fn () => $this->candidates($text))->call($manager);

        $this->assertSame([], $search('e'));
        $this->assertSame([$reachable->id => 'Estación meteorológica'], $search('Estac'));
    }

    #[Test]
    public function the_pages_follow_the_content(): void
    {
        $page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
        $url = ContentResource::getUrl('pages', ['record' => $this->content, 'page' => $page->id]);

        $this->actingAs($this->contributor);
        Livewire::test(ManageContentPages::class, ['record' => $this->content->getRouteKey(), 'page' => $page->id])
            ->assertSet('readOnly', false)
            ->assertActionVisible('deletePage');
        // Otra «pestaña» del mismo usuario: abre, pero en lectura.
        $this->get($url)->assertOk()->assertSee('Ya la tienes abierta en otra pestaña');

        $this->actingAs($this->outsider);
        $this->get($url)->assertNotFound();

        // Quitado de colaborador, deja de poder tocar las páginas.
        app(ContentContributorService::class)->remove($this->content, $this->contributor);
        $this->actingAs($this->contributor);
        $this->get($url)->assertNotFound();
    }

    #[Test]
    public function uploading_to_a_content_follows_the_same_rules(): void
    {
        $upload = fn (User $user) => $this->actingAs($user)->postJson(
            route('admin.contents.editor.files.store', $this->content),
            ['file' => UploadedFile::fake()->image('foto.jpg')],
        );

        $upload($this->contributor)->assertOk();
        $upload($this->outsider)->assertForbidden();
    }

    #[Test]
    public function the_user_form_saves_platforms_with_automatic_contributor(): void
    {
        $this->actingAs(User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]));
        $editor = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        $other = Platform::factory()->create();

        Livewire::test(EditUser::class, ['record' => $editor->getKey()])
            ->fillForm(['platformAssignments' => [
                ['platform_id' => $this->platform->id, 'auto_contributor' => true],
                ['platform_id' => $other->id, 'auto_contributor' => false],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing(
            [[$this->platform->id, true], [$other->id, false]],
            PlatformUser::query()->where('user_id', $editor->id)->get()->map(fn (PlatformUser $row): array => [$row->platform_id, $row->auto_contributor])->all(),
        );

        // El colaborador automático ya se ha aplicado al contenido existente.
        $this->assertTrue($this->content->refresh()->hasContributor($editor));
    }
}
