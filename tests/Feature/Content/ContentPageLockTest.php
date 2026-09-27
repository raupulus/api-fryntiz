<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\ContentPageFormatEnum as Format;
use App\Enums\UserRoleEnum;
use App\Exceptions\ContentPageLockedException;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use App\Services\Content\ContentPageDraftService;
use App\Services\Content\ContentPageFormatService;
use App\Services\Content\ContentPageLockService;
use App\Services\Content\ContentPageLockState as State;
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
 * Bloqueo de cada página a un usuario mientras la edita (P4 de la auditoría de
 * contenidos; F6 del plan del 2026-09-24).
 */
class ContentPageLockTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private ContentPageLockService $locks;

    private User $ana;

    private User $bruno;

    private ContentPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->locks = app(ContentPageLockService::class);
        $this->ana = User::factory()->create(['name' => 'Ana', 'role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        $this->bruno = User::factory()->create(['name' => 'Bruno', 'role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);

        $content = Content::factory()->create(['author_id' => $this->ana->id]);
        app(ContentContributorService::class)->add($content, $this->bruno);

        $this->page = ContentPage::query()->create(['content_id' => $content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);
        app(ContentPageFormatService::class)->save($this->page, Format::EditorJs, $this->editorJs('Hola'));
        $this->page->refresh();
    }

    private function editorJs(string $text): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => [
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => $text]],
        ]]);
    }

    #[Test]
    public function while_ana_has_it_bruno_only_reads(): void
    {
        $this->assertSame(State::MINE, $this->locks->acquire($this->page, $this->ana, 'pestaña-ana')->status);

        Carbon::setTestNow(now()->addMinutes(5));
        $this->assertTrue($this->locks->renew($this->page, $this->ana, 'pestaña-ana'));

        $bruno = $this->locks->acquire($this->page, $this->bruno, 'pestaña-bruno');
        $this->assertSame(State::OTHER_USER, $bruno->status);
        $this->assertSame('Ana la está editando desde hace 5 minutos: se ve en lectura hasta que la deje.', $bruno->message());

        $this->assertThrows(
            fn () => app(ContentPageFormatService::class)->savePage($this->page, [], Format::EditorJs, $this->editorJs('Bruno'), author: $this->bruno),
            ContentPageLockedException::class,
        );
        $this->assertStringContainsString('Hola', (string) $this->page->refresh()->content);
    }

    #[Test]
    public function two_minutes_without_renewing_frees_it(): void
    {
        $this->locks->acquire($this->page, $this->ana, 'pestaña-ana');

        Carbon::setTestNow(now()->addSeconds(90));
        $this->locks->renew($this->page, $this->ana, 'pestaña-ana');

        // 110 s desde la última renovación: sigue siendo de Ana.
        Carbon::setTestNow(now()->addSeconds(110));
        $this->assertSame(State::OTHER_USER, $this->locks->acquire($this->page, $this->bruno, 'pestaña-bruno')->status);

        // Ana deja de renovar (cierra la pestaña): a los 2 minutos, libre.
        Carbon::setTestNow(now()->addSeconds(11));
        $this->assertSame(State::MINE, $this->locks->acquire($this->page, $this->bruno, 'pestaña-bruno')->status);
        $this->assertFalse($this->locks->renew($this->page, $this->ana, 'pestaña-ana'), 'Ana ha perdido el bloqueo.');
    }

    #[Test]
    public function a_second_tab_of_the_same_user_is_read_only(): void
    {
        $this->locks->acquire($this->page, $this->ana, 'primera');

        $second = $this->locks->acquire($this->page, $this->ana, 'segunda');

        $this->assertSame(State::OTHER_TAB, $second->status);
        $this->assertSame('Ya la tienes abierta en otra pestaña: aquí se ve en lectura.', $second->message());
        $this->assertTrue($this->locks->renew($this->page, $this->ana, 'primera'));

        // Guardar desde la pestaña que lo tiene, sí.
        app(ContentPageFormatService::class)->savePage($this->page, [], Format::EditorJs, $this->editorJs('Desde la primera'), author: $this->ana, lockToken: 'primera');
        $this->assertStringContainsString('Desde la primera', (string) $this->page->refresh()->content);
    }

    #[Test]
    public function a_forced_unlock_leaves_the_holder_without_it_and_with_the_draft_intact(): void
    {
        $admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $content = $this->page->contentModel;
        $drafts = app(ContentPageDraftService::class);

        $this->locks->acquire($this->page, $this->ana, 'pestaña-ana');
        $drafts->save($this->ana, $content, $this->page, Format::EditorJs, $this->editorJs('Sin guardar'), 'Página', 'pagina');

        $this->assertThrows(fn () => $this->locks->forceUnlock($this->page, $this->bruno), AuthorizationException::class);
        $this->locks->forceUnlock($this->page, $admin);

        $this->assertFalse($this->locks->renew($this->page, $this->ana, 'pestaña-ana'), '«Bloqueo perdido» en la siguiente renovación.');
        $this->assertSame(State::MINE, $this->locks->acquire($this->page, $this->bruno, 'pestaña-bruno')->status);
        $this->assertStringContainsString('Sin guardar', (string) $drafts->find($this->ana, $content, $this->page)?->content);
    }

    #[Test]
    public function the_same_user_can_take_over_from_another_tab_but_not_from_another_user(): void
    {
        // Una pestaña que murió sin soltarlo (navegador cerrado de golpe).
        $this->locks->acquire($this->page, $this->ana, 'pestaña-muerta');

        $this->assertSame(State::MINE, $this->locks->takeOver($this->page, $this->ana, 'pestaña-nueva')->status);
        $this->assertFalse($this->locks->renew($this->page, $this->ana, 'pestaña-muerta'), 'La otra pestaña pasa a lectura.');
        $this->assertTrue($this->locks->renew($this->page, $this->ana, 'pestaña-nueva'));

        $this->assertSame(State::OTHER_USER, $this->locks->takeOver($this->page, $this->bruno, 'pestaña-bruno')->status);
        $this->assertTrue($this->locks->renew($this->page, $this->ana, 'pestaña-nueva'), 'Bruno no se lo queda.');
    }

    #[Test]
    public function releasing_frees_it_at_once_and_only_the_holder_can(): void
    {
        $this->locks->acquire($this->page, $this->ana, 'pestaña-ana');

        $this->locks->release($this->page, $this->bruno, 'pestaña-ana');
        $this->assertSame(State::OTHER_USER, $this->locks->state($this->page, $this->bruno)->status);

        $this->locks->release($this->page, $this->ana, 'pestaña-ana');
        $this->assertSame(State::FREE, $this->locks->state($this->page, $this->bruno)->status);
    }

    #[Test]
    public function the_lock_does_not_touch_the_page_date(): void
    {
        $before = $this->page->refresh()->updated_at;

        Carbon::setTestNow(now()->addMinute());
        $this->locks->acquire($this->page, $this->ana, 'pestaña-ana');
        $this->locks->renew($this->page, $this->ana, 'pestaña-ana');
        $this->locks->release($this->page, $this->ana, 'pestaña-ana');

        $this->assertEquals($before, $this->page->refresh()->updated_at);
    }
}
