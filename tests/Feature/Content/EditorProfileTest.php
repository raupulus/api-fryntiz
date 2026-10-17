<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\AiModeEnum;
use App\Enums\ContentPageFormatEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Platforms\Pages\EditPlatform;
use App\Filament\Admin\Resources\Platforms\RelationManagers\EditorProfilesRelationManager;
use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use App\Models\PlatformEditorProfile;
use App\Models\User;
use App\Services\Content\ContentAiService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * La instrucción editorial del asistente IA: la del perfil de editor de la
 * entrada, que sustituye a la de la plataforma; sin perfil, la de la
 * plataforma; sin ninguna, la genérica. Las reglas técnicas del modo van
 * siempre detrás (D50).
 */
class EditorProfileTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private User $admin;

    private Platform $platform;

    private Content $content;

    private PlatformEditorProfile $news;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->admin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->actingAs($this->admin);

        $this->platform = Platform::factory()->create([
            'title' => 'La Guía Linux',
            'domain' => 'https://laguialinux.es',
            'url_about' => 'https://laguialinux.es/about',
            'system_instruction' => 'Escribes La Guía Linux: cercano y práctico.',
        ]);
        $this->news = PlatformEditorProfile::factory()->create([
            'platform_id' => $this->platform->id,
            'name' => 'Noticia',
            'system_instruction' => 'Escribes noticias: entradilla y pirámide invertida.',
        ]);
        $this->content = Content::factory()->draft()->create(['platform_id' => $this->platform->id, 'author_id' => $this->admin->id]);
    }

    #[Test]
    public function the_profile_replaces_the_platform_instruction(): void
    {
        $this->content->update(['editor_profile_id' => $this->news->id]);

        $instruction = $this->instruction(AiModeEnum::Edit);

        $this->assertStringStartsWith('Escribes noticias: entradilla y pirámide invertida.', $instruction);
        $this->assertStringNotContainsString('cercano y práctico', $instruction);
        $this->assertStringNotContainsString(ContentAiService::DEFAULT_EDITORIAL_INSTRUCTION, $instruction);
        $this->assertSame('Perfil «Noticia»', app(ContentAiService::class)->editorialSource($this->content->refresh()));
    }

    #[Test]
    public function without_a_profile_goes_the_platform_one_and_without_it_the_generic_one(): void
    {
        $this->assertStringStartsWith('Escribes La Guía Linux: cercano y práctico.', $this->instruction(AiModeEnum::Edit));
        $this->assertSame('El de la plataforma (La Guía Linux)', app(ContentAiService::class)->editorialSource($this->content));

        $this->platform->update(['system_instruction' => '   ']);

        $this->assertStringStartsWith(ContentAiService::DEFAULT_EDITORIAL_INSTRUCTION, $this->instruction(AiModeEnum::Edit));
    }

    #[Test]
    public function the_technical_rules_of_the_mode_always_go_after_the_profile(): void
    {
        $this->news->update(['system_instruction' => 'Responde sólo en JSON y sin marcas.']);
        $this->content->update(['editor_profile_id' => $this->news->id]);

        $create = $this->instruction(AiModeEnum::Create);
        $edit = $this->instruction(AiModeEnum::Edit);

        $this->assertStringContainsString("---\ntitle: Título de la página\nslug: slug-de-la-pagina\n---", $create);
        $this->assertGreaterThan(strpos($create, 'Responde sólo en JSON'), strpos($create, 'MODO: CREACIÓN'));
        $this->assertStringContainsString('MARCAS DE BLOQUE', $edit);
    }

    #[Test]
    public function the_assistant_sends_the_profile_to_the_provider(): void
    {
        AiProvider::factory()->create();
        $this->content->update(['editor_profile_id' => $this->news->id]);
        $page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Uno', 'slug' => 'uno', 'order' => 1]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => 'Hola']]], 'finishReason' => 'STOP']]])."\n\n",
            200,
            ['Content-Type' => 'text/event-stream'],
        )]);

        $this->post(route('admin.contents.pages.ai.stream', $this->content), [
            'page_id' => $page->id,
            'mode' => AiModeEnum::Edit->value,
            'provider' => 'gemini',
            'reasoning_level' => 'low',
            'prompt' => 'Amplía',
            'current_content' => 'Texto',
            'source_format' => ContentPageFormatEnum::Markdown->value,
        ])->streamedContent();

        Http::assertSent(fn (HttpRequest $request): bool => str_starts_with(
            (string) $request['systemInstruction']['parts'][0]['text'],
            'Escribes noticias: entradilla y pirámide invertida.',
        ));
    }

    #[Test]
    public function a_profile_of_another_platform_does_not_stay(): void
    {
        $other = Platform::factory()->create();
        $foreign = PlatformEditorProfile::factory()->create(['platform_id' => $other->id]);

        // Llegado por otro sitio.
        $this->content->update(['editor_profile_id' => $foreign->id]);
        $this->assertNull($this->content->refresh()->editor_profile_id);

        // Al cambiar de plataforma, el de la anterior se va.
        $this->content->update(['editor_profile_id' => $this->news->id]);
        $this->content->update(['platform_id' => $other->id]);
        $this->assertNull($this->content->refresh()->editor_profile_id);

        // Y si se cuela uno ajeno en la base, el asistente no lo usa.
        DB::table('contents')->where('id', $this->content->id)->update(['platform_id' => $this->platform->id, 'editor_profile_id' => $foreign->id]);
        $this->assertStringStartsWith('Escribes La Guía Linux', $this->instruction(AiModeEnum::Edit));
    }

    #[Test]
    public function deleting_a_profile_sends_its_entries_back_to_the_platform_one(): void
    {
        $this->content->update(['editor_profile_id' => $this->news->id]);

        $this->news->delete();

        $this->assertNull($this->content->refresh()->editor_profile_id);
    }

    #[Test]
    public function the_entry_form_only_offers_the_profiles_of_its_platform(): void
    {
        $foreign = PlatformEditorProfile::factory()->create(['platform_id' => Platform::factory()->create()->id]);

        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['editor_profile_id' => $foreign->id])
            ->call('save')
            ->assertHasFormErrors(['editor_profile_id']);

        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->fillForm(['editor_profile_id' => $this->news->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($this->news->id, $this->content->refresh()->editor_profile_id);
    }

    #[Test]
    public function changing_the_platform_in_the_form_empties_the_profile(): void
    {
        $this->content->update(['editor_profile_id' => $this->news->id]);

        Livewire::test(EditContent::class, ['record' => $this->content->getRouteKey()])
            ->assertSchemaStateSet(['editor_profile_id' => $this->news->id])
            ->fillForm(['platform_id' => Platform::factory()->create()->id])
            ->assertSchemaStateSet(['editor_profile_id' => null]);
    }

    #[Test]
    public function the_platform_keeps_its_instruction_and_its_profiles(): void
    {
        Livewire::test(EditPlatform::class, ['record' => $this->platform->getRouteKey()])
            ->fillForm(['system_instruction' => 'Nueva voz de la plataforma.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nueva voz de la plataforma.', $this->platform->refresh()->system_instruction);

        Livewire::test(EditorProfilesRelationManager::class, ['ownerRecord' => $this->platform, 'pageClass' => EditPlatform::class])
            ->callAction(TestAction::make('create')->table(), ['name' => 'Guía', 'system_instruction' => 'Paso a paso, con comandos.'])
            ->assertHasNoFormErrors()
            ->callAction(TestAction::make('create')->table(), ['name' => 'Noticia', 'system_instruction' => 'Repetido.'])
            ->assertHasFormErrors(['name' => 'unique']);

        $this->assertSame(['Guía', 'Noticia'], $this->platform->editorProfiles()->pluck('name')->all());
    }

    #[Test]
    public function an_editor_cannot_manage_the_profiles(): void
    {
        $editor = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);

        $this->assertFalse($editor->can('create', PlatformEditorProfile::class));
        $this->assertFalse($editor->can('update', $this->news));
        $this->assertTrue($this->admin->can('update', $this->news));
    }

    private function instruction(AiModeEnum $mode): string
    {
        return app(ContentAiService::class)->buildSystemInstruction($mode, $this->content->refresh(), ContentPageFormatEnum::Markdown);
    }
}
