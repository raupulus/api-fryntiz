<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\ContentStatusEnum as Status;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\CreateContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContentVisibility;
use App\Filament\Admin\Resources\Content\Contents\Pages\ListContents;
use App\Models\Content\Content;
use App\Models\Platform;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Estado y publicación de un contenido desde el panel: las mismas reglas que
 * el modelo, y el formulario no deja hacer lo que el modelo rechaza.
 */
class ContentPublicationPanelTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();

        $this->actingAs(User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function newContent(array $overrides = []): array
    {
        $type = Content::factory()->make()->type_id;

        return [
            'title' => 'Un contenido nuevo',
            'slug' => 'un-contenido-nuevo',
            'platform_id' => Platform::factory()->create()->id,
            'author_id' => auth()->id(),
            'type_id' => $type,
            ...$overrides,
        ];
    }

    #[Test]
    public function creating_it_published_sets_the_date_and_activates_it(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');

        Livewire::test(CreateContent::class)
            ->fillForm($this->newContent(['status_id' => Status::Published->value, 'is_active' => false]))
            ->call('create')
            ->assertHasNoFormErrors();

        $content = Content::query()->where('slug', 'un-contenido-nuevo')->firstOrFail();
        $this->assertTrue($content->is_active);
        $this->assertSame('2026-09-24 10:00:00', $content->published_at?->toDateTimeString());
    }

    #[Test]
    public function scheduling_needs_a_future_date(): void
    {
        Livewire::test(CreateContent::class)
            ->fillForm($this->newContent(['status_id' => Status::Scheduled->value]))
            ->call('create')
            ->assertHasFormErrors(['scheduled_at' => 'required']);

        Livewire::test(CreateContent::class)
            ->fillForm($this->newContent(['status_id' => Status::Scheduled->value, 'scheduled_at' => now()->subHour()]))
            ->call('create')
            ->assertHasFormErrors(['scheduled_at' => 'after']);

        Livewire::test(CreateContent::class)
            ->fillForm($this->newContent(['status_id' => Status::Scheduled->value, 'scheduled_at' => now()->addDay()]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(Status::Scheduled->value, Content::query()->where('slug', 'un-contenido-nuevo')->value('status_id'));
    }

    #[Test]
    public function the_status_of_a_published_content_cannot_be_changed(): void
    {
        $content = Content::factory()->published()->create();

        Livewire::test(EditContent::class, ['record' => $content->getRouteKey()])
            ->assertFormFieldDisabled('status_id')
            ->fillForm(['status_id' => Status::Draft->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(Status::Published->value, $content->refresh()->status_id);
    }

    #[Test]
    public function a_published_content_is_hidden_from_the_form(): void
    {
        $content = Content::factory()->published()->create();

        // «Activo» está en la sección «Visibilidad» (F7).
        Livewire::test(EditContentVisibility::class, ['record' => $content->getRouteKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $content->refresh();
        $this->assertFalse($content->is_active);
        $this->assertSame(Status::Published->value, $content->status_id);
    }

    #[Test]
    public function the_bulk_publish_action_follows_the_same_rules(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        $draft = Content::factory()->draft()->create();
        $hidden = Content::factory()->hidden()->create(['published_at' => '2025-05-05 05:05:05']);

        Livewire::test(ListContents::class)
            ->selectTableRecords([$draft->getKey(), $hidden->getKey()])
            ->callAction(TestAction::make('publish')->table()->bulk())
            ->assertNotified('Contenidos publicados');

        $draft->refresh();
        $this->assertSame(Status::Published->value, $draft->status_id);
        $this->assertTrue($draft->is_active);
        $this->assertSame('2026-09-24 10:00:00', $draft->published_at?->toDateTimeString());

        // Uno que ya estaba publicado conserva su fecha y vuelve a verse.
        $hidden->refresh();
        $this->assertTrue($hidden->is_active);
        $this->assertSame('2025-05-05 05:05:05', $hidden->published_at?->toDateTimeString());
    }
}
