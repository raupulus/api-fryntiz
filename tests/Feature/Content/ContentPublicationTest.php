<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Actions\PublishContentAction;
use App\Enums\ContentStatusEnum as Status;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Reglas de publicación de los contenidos (P1 y DUDA-3 de la auditoría del
 * 2026-09-24), con los estados en el orden de producción.
 *
 * - Publicar pone la fecha de publicación y marca «Activo».
 * - Publicado es definitivo: sólo se oculta con «Activo» o se elimina.
 * - Un borrador no tiene fecha de publicación.
 * - Programar exige fecha; al llegar, el cron lo publica y lo activa.
 * - A las webs sólo va lo publicado y activo.
 */
class ContentPublicationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Transiciones ────────────────────────────────────────────────────────

    #[Test]
    public function publishing_a_draft_sets_the_date_and_makes_it_visible(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        $content = Content::factory()->draft()->create();

        $content->update(['status_id' => Status::Published->value]);

        $this->assertTrue($content->refresh()->is_active);
        $this->assertSame('2026-09-24 10:00:00', $content->published_at?->toDateTimeString());
    }

    #[Test]
    public function publishing_keeps_a_date_it_already_had(): void
    {
        $content = Content::factory()->published()->create(['published_at' => '2025-01-01 08:00:00']);

        $content->update(['title' => 'Otro título']);

        $this->assertSame('2025-01-01 08:00:00', $content->refresh()->published_at?->toDateTimeString());
    }

    #[Test]
    public function a_draft_has_no_publication_date(): void
    {
        $content = Content::factory()->draft()->create(['published_at' => now()->subWeek()]);

        $this->assertNull($content->refresh()->published_at);
    }

    /**
     * @return array<string, array{Status}>
     */
    public static function statusesOtherThanPublished(): array
    {
        return collect(Status::cases())
            ->reject(fn (Status $status): bool => $status === Status::Published)
            ->mapWithKeys(fn (Status $status): array => [$status->slug() => [$status]])
            ->all();
    }

    #[Test]
    #[DataProvider('statusesOtherThanPublished')]
    public function a_published_content_does_not_change_status(Status $status): void
    {
        $content = Content::factory()->published()->create();

        try {
            $content->update(['status_id' => $status->value, 'scheduled_at' => now()->addDay()]);
            $this->fail("Un contenido publicado ha pasado a «{$status->slug()}».");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status_id', $e->errors());
        }

        $this->assertSame(Status::Published->value, $content->refresh()->status_id);
    }

    #[Test]
    public function a_published_content_is_hidden_with_the_active_flag(): void
    {
        $content = Content::factory()->published()->create();

        $content->update(['is_active' => false]);

        $content->refresh();
        $this->assertSame(Status::Published->value, $content->status_id);
        $this->assertFalse($content->is_active);
        $this->assertNotNull($content->published_at);
    }

    #[Test]
    public function the_publish_action_shows_a_hidden_content_again(): void
    {
        $content = Content::factory()->hidden()->create();

        $content->publish();

        $this->assertTrue($content->refresh()->is_active);
    }

    #[Test]
    public function scheduling_without_a_date_is_rejected(): void
    {
        $content = Content::factory()->draft()->create();

        $this->expectException(ValidationException::class);

        $content->update(['status_id' => Status::Scheduled->value, 'scheduled_at' => null]);
    }

    #[Test]
    public function other_statuses_do_not_touch_the_dates(): void
    {
        $content = Content::factory()->scheduled(now()->addWeek())->create();
        $scheduledAt = $content->scheduled_at?->toDateTimeString();

        $content->update(['status_id' => Status::NotPublished->value]);

        $content->refresh();
        $this->assertSame($scheduledAt, $content->scheduled_at?->toDateTimeString());
        $this->assertNull($content->published_at);
    }

    // ── Cron ────────────────────────────────────────────────────────────────

    #[Test]
    public function the_cron_publishes_and_activates_what_is_due_and_leaves_the_rest(): void
    {
        Carbon::setTestNow('2026-09-24 12:00:00');
        $due = Content::factory()->scheduled(Carbon::parse('2026-09-24 11:58:00'))->create();
        $future = Content::factory()->scheduled(Carbon::parse('2026-09-24 12:30:00'))->create();

        $published = app(PublishContentAction::class)->execute();

        $this->assertSame(1, $published);

        $due->refresh();
        $this->assertSame(Status::Published->value, $due->status_id);
        $this->assertTrue($due->is_active);
        $this->assertSame('2026-09-24 12:00:00', $due->published_at?->toDateTimeString());

        $this->assertSame(Status::Scheduled->value, $future->refresh()->status_id);
        $this->assertNull($future->published_at);
    }

    /**
     * Varios a la vez: el evento `saved` cargaba la plataforma de cada uno de
     * forma perezosa y, fuera de producción, eso revienta con más de uno.
     */
    #[Test]
    public function the_cron_publishes_several_at_once(): void
    {
        $platform = Platform::factory()->create();
        Content::factory()->count(3)->scheduled(now()->subMinute())->create(['platform_id' => $platform->id]);

        $this->assertSame(3, app(PublishContentAction::class)->execute());
        $this->assertSame(3, $platform->contentsActive()->count());
    }

    #[Test]
    public function the_cron_goes_through_the_model_events(): void
    {
        $content = Content::factory()->scheduled(now()->subMinute())->create();
        $saved = [];

        Content::saved(function (Content $model) use (&$saved): void {
            $saved[] = $model->id;
        });

        app(PublishContentAction::class)->execute();

        // Con un `update` masivo no saltaría ningún evento y la caché de la
        // plataforma seguiría enseñando el contenido como programado.
        $this->assertSame([$content->id], $saved);
    }

    #[Test]
    public function the_cron_runs_every_five_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains((string) $event->command, 'content:publish'));

        $this->assertNotNull($event, 'content:publish no está en el planificador.');
        $this->assertSame('*/5 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    // ── Qué llega a las webs ────────────────────────────────────────────────

    #[Test]
    public function only_published_and_active_contents_reach_the_api(): void
    {
        $platform = Platform::factory()->create();
        $visible = Content::factory()->published()->create(['platform_id' => $platform->id]);
        Content::factory()->hidden()->create(['platform_id' => $platform->id]);
        Content::factory()->draft()->create(['platform_id' => $platform->id]);
        Content::factory()->scheduled()->create(['platform_id' => $platform->id]);

        foreach ([Status::NotPublished, Status::CopyrightProtected, Status::ToRemove] as $status) {
            Content::factory()->draft()->create(['platform_id' => $platform->id])
                ->update(['status_id' => $status->value, 'is_active' => true, 'published_at' => now()->subDay()]);
        }

        $response = $this->getJson("/api/v2/platforms/{$platform->slug}/contents")->assertOk();

        $this->assertSame([$visible->id], collect($response->json('data'))->pluck('id')->all());
    }

    #[Test]
    public function the_platform_and_type_statistics_use_the_same_definition(): void
    {
        $platform = Platform::factory()->create();
        $visible = Content::factory()->published()->create(['platform_id' => $platform->id]);
        Content::factory()->hidden()->create(['platform_id' => $platform->id]);
        Content::factory()->draft()->create(['platform_id' => $platform->id]);

        $this->assertSame([$visible->id], $platform->contentsActive()->pluck('contents.id')->all());
        $this->assertSame(
            [$visible->id],
            ContentAvailableType::query()->findOrFail($visible->type_id)->contentsActive()->where('platform_id', $platform->id)->pluck('id')->all(),
        );
    }
}
