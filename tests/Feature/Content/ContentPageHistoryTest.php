<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\UserRoleEnum;
use App\Exceptions\ContentPageConflictException;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageVersion;
use App\Models\User;
use App\Services\Content\ContentPageFormatService;
use App\Services\Content\ContentPageHistoryService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Historial de versiones (G5) y la última red al guardar (D4) de la auditoría
 * de contenidos; F6 del plan del 2026-09-24.
 */
class ContentPageHistoryTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private ContentPageFormatService $pages;

    private User $user;

    private ContentPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->pages = app(ContentPageFormatService::class);
        $this->user = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        $content = Content::factory()->create(['author_id' => $this->user->id]);

        $this->page = ContentPage::query()->create(['content_id' => $content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
        $this->pages->save($this->page, Format::EditorJs, $this->editorJs('Versión 0'));
        $this->page->refresh();
    }

    private function editorJs(string $text): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]],
        ]], JSON_UNESCAPED_UNICODE);
    }

    private function versions(): int
    {
        return ContentPageVersion::query()->where('content_page_id', $this->page->id)->count();
    }

    #[Test]
    public function sixty_different_saves_leave_the_fifty_most_recent(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            Carbon::setTestNow(now()->addSecond());
            $this->pages->savePage($this->page, [], Format::EditorJs, $this->editorJs("Versión {$i}"), author: $this->user);
        }

        $this->assertSame(ContentPageHistoryService::MAX_PER_PAGE, $this->versions());

        // Se van las más antiguas: la más reciente del historial es la 59 (la 60
        // es la que está guardada) y la más antigua, la 10.
        $kept = ContentPageVersion::query()->where('content_page_id', $this->page->id)->orderBy('id')->pluck('content');
        $this->assertStringContainsString('Versión 10', (string) $kept->first());
        $this->assertStringContainsString('Versión 59', (string) $kept->last());
        $this->assertSame($this->user->id, ContentPageVersion::query()->latest('id')->value('user_id'));
    }

    #[Test]
    public function saving_without_changing_the_content_adds_no_version(): void
    {
        $this->pages->savePage($this->page, ['title' => 'Otro título'], Format::EditorJs, $this->editorJs('Versión 0'), author: $this->user);

        $this->assertSame(0, $this->versions());
        $this->assertSame('Otro título', $this->page->refresh()->title);
    }

    #[Test]
    public function the_version_keeps_the_title_it_had(): void
    {
        $this->pages->savePage($this->page, ['title' => 'Título nuevo'], Format::EditorJs, $this->editorJs('Versión 1'), author: $this->user);

        $this->assertSame('Página', ContentPageVersion::query()->value('title'));
    }

    #[Test]
    public function versions_older_than_thirty_days_are_pruned_by_the_daily_task(): void
    {
        $this->pages->savePage($this->page, [], Format::EditorJs, $this->editorJs('Versión 1'), author: $this->user);
        Carbon::setTestNow(now()->addDays(2));
        $this->pages->savePage($this->page, [], Format::EditorJs, $this->editorJs('Versión 2'), author: $this->user);
        $this->assertSame(2, $this->versions());

        // 31 días después de la primera y 29 después de la segunda.
        Carbon::setTestNow(now()->addDays(29));
        $this->artisan('content:prune-drafts-and-versions')->assertSuccessful();

        $this->assertSame(1, $this->versions());
        $this->assertStringContainsString('Versión 1', (string) ContentPageVersion::query()->value('content'));
    }

    #[Test]
    public function a_page_saved_elsewhere_after_opening_it_is_not_overwritten(): void
    {
        $openedAt = $this->page->refresh()->updated_at;

        Carbon::setTestNow(now()->addMinute());
        $this->pages->savePage($this->page, [], Format::EditorJs, $this->editorJs('Lo de la otra pestaña'), author: $this->user, openedAt: $openedAt);
        $otherSavedAt = $this->page->refresh()->updated_at;

        // Una pestaña abierta desde antes intenta guardar.
        Carbon::setTestNow(now()->addMinute());
        $this->assertThrows(
            fn () => $this->pages->savePage(ContentPage::query()->find($this->page->id), ['title' => 'Pisado'], Format::Markdown, 'Lo de la pestaña vieja', author: $this->user, openedAt: $openedAt),
            ContentPageConflictException::class,
        );

        $this->page->refresh();
        $this->assertStringContainsString('Lo de la otra pestaña', (string) $this->page->content);
        $this->assertSame('Página', $this->page->title);
        $this->assertSame(Format::EditorJs, $this->pages->sourceFormat($this->page));

        // Con la fecha buena, sí.
        $this->pages->savePage($this->page, [], Format::EditorJs, $this->editorJs('Ahora sí'), author: $this->user, openedAt: $otherSavedAt);
        $this->assertStringContainsString('Ahora sí', (string) $this->page->refresh()->content);
    }

    #[Test]
    public function both_daily_tasks_are_scheduled(): void
    {
        $daily = collect(app(Schedule::class)->events())
            ->filter(fn (Event $event): bool => $event->expression === '0 4 * * *' || $event->expression === '15 4 * * *')
            ->map(fn (Event $event): string => (string) $event->command)
            ->implode(' | ');

        $this->assertStringContainsString('content:prune-drafts-and-versions', $daily);
        $this->assertStringContainsString('content:purge-unused-files', $daily);
    }
}
