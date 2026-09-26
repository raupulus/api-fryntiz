<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\UserRoleEnum;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentCategory;
use App\Models\Content\ContentContributor;
use App\Models\Content\ContentTag;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformUser;
use App\Models\Tag;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Colaboradores, colaborador automático y relaciones con tabla intermedia
 * (F5 del plan de contenidos del 2026-09-24).
 */
class ContentContributorsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();

        $this->platform = Platform::factory()->create();
    }

    private function editor(): User
    {
        return User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
    }

    private function content(?User $author = null): Content
    {
        return Content::factory()->create(['platform_id' => $this->platform->id, 'author_id' => $author?->id]);
    }

    // ── Filas borradas del pivote ───────────────────────────────────────────

    #[Test]
    public function a_removed_contributor_loses_access(): void
    {
        $editor = $this->editor();
        $content = $this->content();
        $service = app(ContentContributorService::class);

        $service->add($content, $editor);
        $this->assertTrue($editor->can('update', $content));

        $service->remove($content, $editor);

        // La fila sigue ahí, borrada: es lo que marca la baja manual.
        $this->assertSame(1, ContentContributor::onlyTrashed()->where('content_id', $content->id)->count());
        $this->assertFalse($content->hasContributor($editor));
        $this->assertFalse($editor->fresh()->can('update', $content->fresh()));
        $this->assertSame([], $editor->contributedContents()->pluck('contents.id')->all());
    }

    #[Test]
    public function adding_again_recovers_the_row_instead_of_duplicating_it(): void
    {
        $editor = $this->editor();
        $content = $this->content();
        $service = app(ContentContributorService::class);

        $service->add($content, $editor);
        $service->remove($content, $editor);
        $service->add($content, $editor);

        $this->assertSame(1, ContentContributor::withTrashed()->where('content_id', $content->id)->count());
        $this->assertTrue($content->hasContributor($editor));
    }

    #[Test]
    public function the_author_is_never_a_contributor_of_their_own_content(): void
    {
        $author = $this->editor();
        $content = $this->content($author);

        app(ContentContributorService::class)->add($content, $author);

        $this->assertSame(0, ContentContributor::withTrashed()->where('content_id', $content->id)->count());
    }

    // ── Colaborador automático (DUDA-1) ─────────────────────────────────────

    #[Test]
    public function a_new_content_gets_the_automatic_contributors_of_its_platform(): void
    {
        $auto = $this->editor();
        $manual = $this->editor();
        $admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value]);
        PlatformUser::query()->create(['user_id' => $auto->id, 'platform_id' => $this->platform->id, 'auto_contributor' => true]);
        PlatformUser::query()->create(['user_id' => $manual->id, 'platform_id' => $this->platform->id, 'auto_contributor' => false]);
        PlatformUser::query()->create(['user_id' => $admin->id, 'platform_id' => $this->platform->id, 'auto_contributor' => true]);

        $content = $this->content();
        $ownContent = $this->content($auto);

        $this->assertSame([$auto->id], $content->contributors()->pluck('users.id')->all());
        // En lo suyo es autor, no colaborador.
        $this->assertSame([], $ownContent->contributors()->pluck('users.id')->all());
    }

    #[Test]
    public function activating_it_joins_the_existing_contents_except_the_manually_removed_ones(): void
    {
        $editor = $this->editor();
        $first = $this->content();
        $removedFrom = $this->content();
        $own = $this->content($editor);
        $elsewhere = Content::factory()->create(['platform_id' => Platform::factory()->create()->id]);

        $service = app(ContentContributorService::class);
        $service->add($removedFrom, $editor);
        $service->remove($removedFrom, $editor);

        $assignment = PlatformUser::query()->create(['user_id' => $editor->id, 'platform_id' => $this->platform->id]);
        $this->assertSame([], $editor->contributedContents()->pluck('contents.id')->all());

        $assignment->update(['auto_contributor' => true]);

        $this->assertSame([$first->id], $editor->contributedContents()->pluck('contents.id')->all());
        $this->assertFalse($removedFrom->hasContributor($editor));
        $this->assertFalse($own->hasContributor($editor));
        $this->assertFalse($elsewhere->hasContributor($editor));
    }

    #[Test]
    public function deactivating_it_leaves_them_where_they_are_and_stops_the_new_ones(): void
    {
        $editor = $this->editor();
        $before = $this->content();
        $assignment = PlatformUser::query()->create(['user_id' => $editor->id, 'platform_id' => $this->platform->id, 'auto_contributor' => true]);
        $this->assertTrue($before->hasContributor($editor));

        $assignment->update(['auto_contributor' => false]);
        $after = $this->content();

        $this->assertTrue($before->hasContributor($editor));
        $this->assertFalse($after->hasContributor($editor));
    }

    // ── Los métodos de guardado de la relación ──────────────────────────────

    #[Test]
    public function saving_no_contributors_removes_the_link_and_never_the_users(): void
    {
        $content = $this->content();
        $editors = [$this->editor(), $this->editor()];
        $content->saveContributors(array_map(fn (User $user): int => $user->id, $editors));
        $this->assertCount(2, $content->contributors()->get());

        $content->saveContributors([]);

        $this->assertCount(0, $content->contributors()->get());
        foreach ($editors as $editor) {
            $this->assertNotNull(User::query()->find($editor->id), 'Se ha borrado un usuario.');
        }
    }

    #[Test]
    public function saving_tags_keeps_the_ones_still_marked_and_recovers_the_ones_that_come_back(): void
    {
        $content = $this->content();
        [$php, $iot, $solar] = array_map(fn (string $name): Tag => Tag::query()->create(['name' => $name, 'slug' => $name]), ['php', 'iot', 'solar']);

        $content->saveTags([$php->id, $iot->id]);
        $content->saveTags([$iot->id, $solar->id]);
        $content->saveTags([$php->id, $iot->id, $solar->id]);

        $this->assertEqualsCanonicalizing([$php->id, $iot->id, $solar->id], $content->tagsQuery()->pluck('id')->all());
        // Una fila por etiqueta: la que se quitó y volvió se ha recuperado.
        $this->assertSame(3, ContentTag::withTrashed()->where('content_id', $content->id)->count());
    }

    #[Test]
    public function saving_categories_uses_the_platform_ones_and_marks_the_main_one(): void
    {
        $content = $this->content();
        [$hardware, $software, $foreign] = array_map(fn (string $name): Category => Category::query()->create(['name' => $name, 'slug' => $name]), ['hardware', 'software', 'ajena']);
        PlatformCategory::query()->create(['platform_id' => $this->platform->id, 'category_id' => $hardware->id]);
        PlatformCategory::query()->create(['platform_id' => $this->platform->id, 'category_id' => $software->id]);

        $content->saveCategories([$hardware->id, $software->id, $foreign->id], mainCategoryId: $software->id);

        $this->assertEqualsCanonicalizing([$hardware->id, $software->id], $content->categoriesQuery()->pluck('id')->all());
        $this->assertSame(1, ContentCategory::query()->where('content_id', $content->id)->where('is_main', true)->count());

        $content->saveCategories([$hardware->id]);

        $this->assertSame([$hardware->id], $content->categoriesQuery()->pluck('id')->all());
    }
}
