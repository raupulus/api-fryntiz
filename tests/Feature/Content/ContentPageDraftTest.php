<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\ContentPageVersionReasonEnum as Reason;
use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageDraft;
use App\Models\Content\ContentPageVersion;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Borradores en el servidor (D1 y P4 de la auditoría de contenidos; F6 del
 * plan del 2026-09-24).
 */
class ContentPageDraftTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private ContentPageDraftService $drafts;

    private ContentPageFormatService $pages;

    private User $author;

    private User $contributor;

    private Content $content;

    private ContentPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->drafts = app(ContentPageDraftService::class);
        $this->pages = app(ContentPageFormatService::class);

        $editor = fn (): User => User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        [$this->author, $this->contributor] = [$editor(), $editor()];

        $this->content = Content::factory()->create(['author_id' => $this->author->id]);
        app(ContentContributorService::class)->add($this->content, $this->contributor);

        $this->page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
        $this->pages->save($this->page, Format::EditorJs, $this->editorJs('Guardado'));
        $this->page->refresh();
    }

    private function editorJs(string $text, int $time = 1): string
    {
        return (string) json_encode(['time' => $time, 'version' => '2.31.7', 'blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]],
        ]]);
    }

    private function draft(User $user, string $text, int $time = 1): ?ContentPageDraft
    {
        return $this->drafts->save($user, $this->content, $this->page, Format::EditorJs, $this->editorJs($text, $time), 'Página', 'pagina');
    }

    #[Test]
    public function twenty_autosaves_with_the_same_content_leave_one_draft_and_no_versions(): void
    {
        $first = $this->draft($this->author, 'Escribiendo', 1);
        $firstUpdate = $first?->updated_at;

        // Editor.js cambia su marca de tiempo en cada guardado aunque no cambie nada.
        for ($i = 2; $i <= 20; $i++) {
            Carbon::setTestNow(now()->addSeconds(30));
            $this->draft($this->author, 'Escribiendo', $i);
        }

        $this->assertSame(1, ContentPageDraft::query()->count());
        $this->assertEquals($firstUpdate, ContentPageDraft::query()->first()?->updated_at, 'Un borrador igual no se vuelve a escribir.');
        $this->assertSame(0, ContentPageVersion::query()->count());
    }

    #[Test]
    public function what_equals_the_saved_page_is_not_a_draft(): void
    {
        $this->draft($this->author, 'Algo distinto');
        $this->assertSame(1, ContentPageDraft::query()->count());

        // Vuelve a dejarlo como estaba guardado: el borrador sobra.
        $this->assertNull($this->draft($this->author, 'Guardado', 7));
        $this->assertSame(0, ContentPageDraft::query()->count());
    }

    #[Test]
    public function a_draft_is_only_offered_to_whoever_wrote_it(): void
    {
        $draft = $this->draft($this->author, 'Lo mío');

        $this->assertNotNull($this->drafts->find($this->author, $this->content, $this->page));
        $this->assertNull($this->drafts->find($this->contributor, $this->content, $this->page));

        $this->assertThrows(fn () => $this->drafts->restore($draft, $this->contributor), AuthorizationException::class);
        $this->assertThrows(fn () => $this->drafts->discard($draft, $this->contributor), AuthorizationException::class);
        $this->assertStringContainsString('Guardado', (string) $this->page->refresh()->content);
    }

    #[Test]
    public function a_draft_warns_when_the_page_was_saved_after_it(): void
    {
        $draft = $this->draft($this->author, 'Mi borrador');
        $this->assertFalse($this->drafts->isOutdated($draft));

        Carbon::setTestNow(now()->addMinute());
        $this->pages->savePage($this->page->refresh(), [], Format::EditorJs, $this->editorJs('Lo de la colaboradora'), author: $this->contributor);

        $this->assertTrue($this->drafts->isOutdated($draft->refresh()));
    }

    #[Test]
    public function restoring_a_draft_keeps_what_was_saved_in_the_history(): void
    {
        $draft = $this->draft($this->author, 'Recuperado');

        $this->drafts->restore($draft, $this->author);

        $this->assertStringContainsString('Recuperado', (string) $this->page->refresh()->content);

        $version = ContentPageVersion::query()->where('content_page_id', $this->page->id)->latest('id')->first();
        $this->assertSame(Reason::DraftRestore, $version?->reason);
        $this->assertStringContainsString('Guardado', (string) $version?->content);
        $this->assertSame(0, ContentPageDraft::query()->count());
    }

    #[Test]
    public function saving_the_page_deletes_only_the_draft_of_whoever_saves(): void
    {
        $this->draft($this->author, 'Borrador de la autora');
        $this->draft($this->contributor, 'Borrador del colaborador');

        $this->pages->savePage($this->page, [], Format::EditorJs, $this->editorJs('Guardado por la autora'), author: $this->author);

        $this->assertNull($this->drafts->find($this->author, $this->content, $this->page));
        $this->assertNotNull($this->drafts->find($this->contributor, $this->content, $this->page));
    }

    #[Test]
    public function the_draft_of_a_new_page_becomes_a_new_page(): void
    {
        $draft = $this->drafts->save($this->author, $this->content, null, Format::Markdown, "Una página *nueva*\n", 'Nueva', 'nueva');

        $page = $this->drafts->restore($draft, $this->author);

        $this->assertTrue($page->exists);
        $this->assertSame('Nueva', $page->title);
        $this->assertSame(2, $page->order);
        $this->assertSame(Format::Markdown, $this->pages->sourceFormat($page));
        $this->assertSame(0, ContentPageDraft::query()->count());
    }

    #[Test]
    public function drafts_older_than_thirty_days_are_pruned(): void
    {
        $this->draft($this->author, 'Viejo');
        Carbon::setTestNow(now()->addDays(2));
        $this->draft($this->contributor, 'Reciente');

        Carbon::setTestNow(now()->addDays(29));
        $this->artisan('content:prune-drafts-and-versions')->assertSuccessful();

        $this->assertNull($this->drafts->find($this->author, $this->content, $this->page));
        $this->assertNotNull($this->drafts->find($this->contributor, $this->content, $this->page));
    }
}
