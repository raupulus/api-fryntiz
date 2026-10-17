<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\AiModeEnum;
use App\Enums\AiProviderEnum;
use App\Enums\AiReasoningLevelEnum;
use App\Enums\ContentPageFormatEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\User;
use App\Services\Content\ContentAiService;
use App\Services\Content\ContentContributorService;
use App\Services\Content\ContentFormatConverter;
use App\Services\Content\ContentPageFormatService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * El asistente de IA del editor de páginas, con Gemini como proveedor. Los
 * conectores de Mistral y de la API Responses (OpenAI y Grok) tienen sus
 * propias pruebas en `tests/Feature/Ai`.
 */
class ContentPageAiAssistantTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private User $admin;

    private Content $content;

    private ContentPage $page;

    private AiProvider $assistant;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->gemini = AiProvider::factory()->create();

        $this->admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);

        $this->content = Content::factory()->create([
            'author_id' => $this->admin->id,
            'title' => 'Tutorial de Laravel y Vue',
            'excerpt' => 'Guía paso a paso para desarrollo web moderno',
        ]);

        $this->page = ContentPage::query()->create([
            'content_id' => $this->content->id,
            'title' => 'Introducción',
            'slug' => 'introduccion',
            'order' => 1,
        ]);
    }

    // ── Petición a Google ───────────────────────────────────────────────────

    #[Test]
    public function the_key_travels_in_a_header_and_the_request_uses_gemini_38_with_thinking_level_and_thought_summaries(): void
    {
        $this->fakeGemini($this->sse(['Contenido generado.']));

        // El stream corre al leer la respuesta.
        $this->stream(['mode' => AiModeEnum::Edit->value, 'reasoning_level' => 'high'])->streamedContent();

        Http::assertSent(function (HttpRequest $request): bool {
            return str_contains($request->url(), '/models/gemini-3.8-flash:streamGenerateContent?alt=sse')
                && ! str_contains($request->url(), 'key=')
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && $request['generationConfig'] === ['thinkingConfig' => ['thinkingLevel' => 'high', 'includeThoughts' => true]];
        });
    }

    #[Test]
    public function the_stream_sends_thoughts_content_and_the_result(): void
    {
        $this->fakeGemini(
            "data: {\"candidates\": [{\"content\": {\"parts\": [{\"text\": \"Razonando...\", \"thought\": true}]}}]}\n\n".
            "data: {\"candidates\": [{\"content\": {\"parts\": [{\"text\": \"Contenido generado.\"}]}, \"finishReason\": \"STOP\"}]}\n\n"
        );

        $content = $this->stream(['current_content' => 'Texto anterior'])->streamedContent();

        $this->assertStringContainsString("event: thought\ndata: {\"text\":\"Razonando...\"}", $content);
        $this->assertStringContainsString("event: content\ndata: {\"text\":\"Contenido generado.\"}", $content);
        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString('event: done', $content);
    }

    #[Test]
    public function an_error_from_google_ends_the_stream_without_an_empty_result(): void
    {
        $this->fakeGemini(['error' => ['code' => 400, 'message' => 'API key not valid. Please pass a valid API key.']], 400);

        $content = $this->stream()->streamedContent();

        $this->assertStringContainsString('event: error', $content);
        $this->assertStringContainsString('API key not valid', $content);
        $this->assertStringNotContainsString('event: complete', $content);
        $this->assertStringNotContainsString('event: done', $content);
    }

    #[Test]
    public function an_account_without_credit_says_so(): void
    {
        $this->fakeGemini(['error' => ['code' => 402, 'message' => 'Your prepayment credits are depleted.']], 402);

        $this->assertStringContainsString('no tiene saldo', $this->stream()->streamedContent());
    }

    #[Test]
    public function a_connection_failure_does_not_reach_the_browser_with_its_details(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timeout for https://generativelanguage.googleapis.com/v1beta/models/x?key=SECRETO'));

        $content = $this->stream()->streamedContent();

        $this->assertStringContainsString('No se ha podido conectar con Gemini', $content);
        $this->assertStringNotContainsString('SECRETO', $content);
        $this->assertStringNotContainsString('cURL', $content);
    }

    #[Test]
    public function an_empty_answer_is_an_error_and_not_a_silent_result(): void
    {
        $this->fakeGemini("data: {\"candidates\": [{\"content\": {\"parts\": []}, \"finishReason\": \"STOP\"}]}\n\n");

        $content = $this->stream()->streamedContent();

        $this->assertStringContainsString('no ha devuelto ningún texto', $content);
        $this->assertStringNotContainsString('event: complete', $content);
    }

    #[Test]
    public function a_blocked_prompt_says_why(): void
    {
        $this->fakeGemini("data: {\"promptFeedback\": {\"blockReason\": \"SAFETY\"}}\n\n");

        $content = $this->stream()->streamedContent();

        $this->assertStringContainsString('Gemini ha bloqueado la petición (SAFETY)', $content);
        $this->assertStringNotContainsString('event: complete', $content);
    }

    #[Test]
    public function a_cut_answer_comes_with_a_warning(): void
    {
        $this->fakeGemini("data: {\"candidates\": [{\"content\": {\"parts\": [{\"text\": \"Texto a medias\"}]}, \"finishReason\": \"MAX_TOKENS\"}]}\n\n");

        $content = $this->stream()->streamedContent();

        $this->assertStringContainsString('event: complete', $content);
        $this->assertStringContainsString('se ha cortado por larga', $content);
    }

    #[Test]
    public function the_reasoning_level_has_to_be_one_of_the_list(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.contents.pages.ai.stream', $this->content), $this->payload(['reasoning_level' => 'minimal']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reasoning_level']);
    }

    #[Test]
    public function without_reasoning_level_the_request_carries_no_thinking_level(): void
    {
        $this->gemini->update(['sends_reasoning_level' => false]);
        $this->fakeGemini($this->sse(['Texto.']));

        $this->stream(['reasoning_level' => 'high'])->streamedContent();

        Http::assertSent(fn (HttpRequest $request): bool => $request['generationConfig'] === ['thinkingConfig' => ['includeThoughts' => true]]);
    }

    #[Test]
    public function only_an_active_provider_with_a_readable_key_can_be_asked(): void
    {
        AiProvider::factory()->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5')->create(['is_active' => false]);
        // Cifrada con otra APP_KEY: no se puede leer.
        $grok = AiProvider::factory()->provider(AiProviderEnum::Xai, 'grok-4.7')->create();
        $grok->newQuery()->whereKey($grok->id)->update(['api_key' => 'eyJpdiI6ImNpZnJhZGEtY29uLW90cmEta2V5In0=']);

        foreach (['mistral', 'xai', 'openai'] as $provider) {
            $this->actingAs($this->admin)
                ->postJson(route('admin.contents.pages.ai.stream', $this->content), $this->payload(['provider' => $provider]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['provider']);
        }
    }

    #[Test]
    public function the_assistant_has_a_wide_limit_per_user(): void
    {
        Config::set('rate_limits.content_ai_per_minute', 3);
        $this->fakeGemini($this->sse(['Texto.']));

        foreach (range(1, 3) as $ignored) {
            $this->stream()->assertOk();
        }

        $this->actingAs($this->admin)
            ->postJson(route('admin.contents.pages.ai.stream', $this->content), $this->payload())
            ->assertStatus(429);
    }

    #[Test]
    public function stream_endpoint_requires_authentication(): void
    {
        $this->postJson(route('admin.contents.pages.ai.stream', $this->content), $this->payload())->assertUnauthorized();
    }

    #[Test]
    public function stream_endpoint_forbids_unauthorized_user(): void
    {
        $regularUser = User::factory()->create(['role_id' => UserRoleEnum::User->value, 'is_active' => true]);

        $this->actingAs($regularUser)
            ->postJson(route('admin.contents.pages.ai.stream', $this->content), $this->payload())
            ->assertForbidden();
    }

    #[Test]
    public function stream_endpoint_returns_404_when_page_does_not_belong_to_content(): void
    {
        $otherContent = Content::factory()->create(['author_id' => $this->admin->id]);
        $otherPage = ContentPage::query()->create(['content_id' => $otherContent->id, 'title' => 'Ajena', 'slug' => 'ajena', 'order' => 1]);

        $this->actingAs($this->admin)
            ->post(route('admin.contents.pages.ai.stream', $this->content), $this->payload(['page_id' => $otherPage->id]))
            ->assertNotFound();
    }

    // ── Bloques que la IA no ve ─────────────────────────────────────────────

    #[Test]
    public function editing_an_editorjs_page_keeps_uploaded_images_and_special_blocks_intact(): void
    {
        $assistant = app(ContentAiService::class);
        $original = $this->editorJs([
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Texto con erata']],
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => ['url' => 'https://api.raupulus.dev/file/get/content/5', 'file_id' => 5], 'caption' => 'Pie de foto']],
            ['id' => 'a1', 'type' => 'alert', 'data' => ['type' => 'info', 'message' => 'Ojo con esto']],
        ]);

        $prepared = $assistant->prepareCurrentContent($original, ContentPageFormatEnum::EditorJs);

        // A Gemini le llega Markdown con dos marcas, no el JSON ni el HTML.
        $this->assertStringContainsString('Texto con erata', $prepared['text']);
        $this->assertStringContainsString('[[BLOQUE_1]]', $prepared['text']);
        $this->assertStringContainsString('[[BLOQUE_2]]', $prepared['text']);
        $this->assertStringNotContainsString('data-editorjs-block', $prepared['text']);
        $this->assertStringNotContainsString('"blocks"', $prepared['text']);

        // Gemini corrige, cambia de sitio una marca y la escapa como Markdown.
        $answer = "Texto con errata corregida\n\n\\[\\[BLOQUE_2\\]\\]\n\n[[BLOQUE_1]]";
        $result = $assistant->convertResultToFormat($answer, ContentPageFormatEnum::EditorJs, AiModeEnum::Correct, $prepared['placeholders']);
        $blocks = json_decode($result['content'], true)['blocks'];

        $this->assertSame(['paragraph', 'alert', 'image'], array_column($blocks, 'type'));
        $this->assertSame('Texto con errata corregida', $blocks[0]['data']['text']);
        $this->assertSame(5, $blocks[2]['data']['file']['file_id']);
        $this->assertSame('Pie de foto', $blocks[2]['data']['caption']);
        $this->assertSame('Ojo con esto', $blocks[1]['data']['message']);
    }

    #[Test]
    public function a_block_that_gemini_removes_is_reported(): void
    {
        $assistant = app(ContentAiService::class);
        $prepared = $assistant->prepareCurrentContent($this->editorJs([
            ['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']],
            ['id' => 'i1', 'type' => 'image', 'data' => ['file' => ['url' => 'https://x.test/file/get/content/5', 'file_id' => 5], 'caption' => 'Pie']],
        ]), ContentPageFormatEnum::EditorJs);

        $result = $assistant->convertResultToFormat('Hola, mundo', ContentPageFormatEnum::EditorJs, AiModeEnum::Edit, $prepared['placeholders']);

        $this->assertStringContainsString('La IA ha quitado 1 bloque que no podía editar (imagen)', implode(' ', $result['warnings']));
    }

    #[Test]
    public function correcting_an_html_page_puts_the_blocks_back_where_gemini_left_them(): void
    {
        $assistant = app(ContentAiService::class);
        $html = "<p>Hola</p>\n\n".app(ContentFormatConverter::class)->wrapBlock(
            ['id' => 'a1', 'type' => 'alert', 'data' => ['type' => 'info', 'message' => 'Aviso']],
        );

        $prepared = $assistant->prepareCurrentContent($html, ContentPageFormatEnum::Html);
        $result = $assistant->convertResultToFormat("```html\n<p>Hola corregido</p>\n<p>[[BLOQUE_1]]</p>\n```", ContentPageFormatEnum::Html, AiModeEnum::Correct, $prepared['placeholders']);

        $this->assertStringStartsWith('<p>Hola corregido</p>', $result['content']);
        $this->assertStringContainsString($prepared['placeholders'][1], $result['content']);
        $this->assertStringNotContainsString('<p><div', $result['content']);
    }

    #[Test]
    public function the_current_page_reaches_gemini_prepared_and_not_as_json(): void
    {
        $this->fakeGemini($this->sse(['Hola corregido']));

        $this->stream([
            'mode' => AiModeEnum::Correct->value,
            'source_format' => ContentPageFormatEnum::EditorJs->value,
            'current_content' => $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Ola']]]),
        ])->streamedContent();

        Http::assertSent(function (HttpRequest $request): bool {
            $prompt = (string) $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, "(en Markdown) ===\nOla") && ! str_contains($prompt, '"blocks"');
        });
    }

    #[Test]
    public function service_builds_context_prompt_with_siblings_and_metadata(): void
    {
        ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Instalación', 'slug' => 'instalacion', 'order' => 2]);

        $prompt = app(ContentAiService::class)->buildContextPrompt(AiModeEnum::Edit, $this->content, $this->page, 'Amplía la explicación', 'Contenido breve actual');

        $this->assertStringContainsString('Tutorial de Laravel y Vue', $prompt);
        $this->assertStringContainsString('Guía paso a paso', $prompt);
        $this->assertStringContainsString('Página 1: Introducción', $prompt);
        $this->assertStringContainsString('Página 2: Instalación', $prompt);
        $this->assertStringContainsString('Amplía la explicación', $prompt);
        $this->assertStringContainsString('Contenido breve actual', $prompt);
    }

    #[Test]
    public function service_parses_created_page_with_frontmatter(): void
    {
        $parsed = app(ContentAiService::class)->parseCreatedPage("---\ntitle: Configuración del entorno\nslug: configuracion-del-entorno\n---\n\n### Pasos previos\n\nAquí están las instrucciones.", ContentPageFormatEnum::Markdown);

        $this->assertSame('Configuración del entorno', $parsed['title']);
        $this->assertSame('configuracion-del-entorno', $parsed['slug']);
        $this->assertStringContainsString('### Pasos previos', $parsed['content']);
    }

    #[Test]
    public function service_parses_created_page_with_crlf_and_markdown_fences(): void
    {
        $parsed = app(ContentAiService::class)->parseCreatedPage("```markdown\r\n---\r\ntitle: Página con CRLF\r\nslug: pagina-con-crlf\r\n---\r\n\r\n### Contenido\r\n\r\nTexto de prueba.\r\n```", ContentPageFormatEnum::Markdown);

        $this->assertSame('Página con CRLF', $parsed['title']);
        $this->assertSame('pagina-con-crlf', $parsed['slug']);
        $this->assertStringContainsString('### Contenido', $parsed['content']);
        $this->assertStringNotContainsString('```', $parsed['content']);
    }

    #[Test]
    public function service_handles_html_format_in_correct_mode_preserving_tags_and_fences(): void
    {
        $result = app(ContentAiService::class)->convertResultToFormat("```html\n<p>Texto con <strong>etiquetas preservadas</strong> y <a href=\"https://raupulus.dev\">enlaces</a>.</p>\n```", ContentPageFormatEnum::Html, AiModeEnum::Correct);

        $this->assertSame('<p>Texto con <strong>etiquetas preservadas</strong> y <a href="https://raupulus.dev">enlaces</a>.</p>', $result['content']);
        $this->assertEmpty($result['warnings']);
    }

    #[Test]
    public function without_a_usable_provider_the_assistant_is_not_available(): void
    {
        $service = app(ContentAiService::class);
        $this->assertTrue($service->isAvailable());

        $this->gemini->update(['is_active' => false]);

        $this->assertFalse($service->isAvailable());
    }

    #[Test]
    public function the_system_instruction_ends_with_the_technical_rules_of_the_mode(): void
    {
        $instruction = app(ContentAiService::class)->buildSystemInstruction(AiModeEnum::Create, $this->content, ContentPageFormatEnum::Markdown);

        $this->assertStringStartsWith(ContentAiService::DEFAULT_EDITORIAL_INSTRUCTION, $instruction);
        $this->assertStringContainsString("el título general del contenido es 'Tutorial de Laravel y Vue'", $instruction);
        $this->assertStringContainsString("title: Título de la página\nslug: slug-de-la-pagina", $instruction);
    }

    // ── Aplicar en el editor ────────────────────────────────────────────────

    #[Test]
    public function livewire_apply_ai_content_replaces_or_appends_in_editor(): void
    {
        $component = $this->editor($this->page)
            ->set('data.source_format', ContentPageFormatEnum::Markdown->value)
            ->set('data.content_markdown', 'Texto inicial.')
            ->call('applyAiContent', 'Nuevo texto generado por Gemini.', false)
            ->assertSet('data.content_markdown', 'Nuevo texto generado por Gemini.')
            ->assertSet('hasUnsavedChanges', true);

        $component->call('applyAiContent', 'Párrafo añadido al final.', true)
            ->assertSet('data.content_markdown', "Nuevo texto generado por Gemini.\n\nPárrafo añadido al final.");
    }

    #[Test]
    public function livewire_apply_ai_content_merges_editorjs_blocks_when_appending(): void
    {
        $result = (string) $this->editor($this->page)
            ->set('data.source_format', ContentPageFormatEnum::EditorJs->value)
            ->set('data.content_json', $this->editorJs([['id' => 'b1', 'type' => 'paragraph', 'data' => ['text' => 'Párrafo uno']]]))
            ->call('applyAiContent', $this->editorJs([['id' => 'b2', 'type' => 'paragraph', 'data' => ['text' => 'Párrafo dos']]]), true)
            ->get('data.content_json');

        $this->assertSame(['b1', 'b2'], array_column(json_decode($result, true)['blocks'], 'id'));
    }

    #[Test]
    public function what_gemini_applied_can_be_undone_and_saving_forgets_it(): void
    {
        app(ContentPageFormatService::class)->save($this->page, ContentPageFormatEnum::Markdown, 'Texto guardado.');

        $component = $this->editor($this->page->refresh())
            ->set('data.content_markdown', 'Lo que escribí sin guardar.')
            ->call('applyAiContent', 'Texto de Gemini.', false)
            ->assertSet('aiUndoAvailable', true)
            ->assertActionVisible('undoAi')
            ->callAction('undoAi')
            ->assertSet('data.content_markdown', 'Lo que escribí sin guardar.')
            ->assertSet('aiUndoAvailable', false);

        $component->call('applyAiContent', 'Texto de Gemini.', false)
            ->call('save')
            ->assertSet('aiUndoAvailable', false)
            ->assertActionHidden('undoAi');

        $this->assertStringContainsString('Texto de Gemini.', (string) $this->page->refresh()->content);
    }

    #[Test]
    public function livewire_apply_ai_new_page_fills_form_when_on_new_page_mode(): void
    {
        $this->editor('new')
            ->set('data.source_format', ContentPageFormatEnum::Markdown->value)
            ->call('applyAiNewPage', 'Página borrador', 'pagina-borrador', 'Texto preparado.')
            ->assertSet('data.title', 'Página borrador')
            ->assertSet('data.slug', 'pagina-borrador')
            ->assertSet('data.content_markdown', 'Texto preparado.')
            ->assertSet('hasUnsavedChanges', true);

        $this->assertSame(1, ContentPage::query()->count());
    }

    #[Test]
    public function creating_a_page_from_an_open_one_saves_the_open_one_first_and_releases_its_lock(): void
    {
        app(ContentPageFormatService::class)->save($this->page, ContentPageFormatEnum::Markdown, 'Texto guardado.');

        $this->editor($this->page->refresh())
            ->set('data.content_markdown', 'Cambio sin guardar.')
            ->call('applyAiNewPage', 'Nueva sección IA', 'nueva-seccion-ia', 'Cuerpo de la página nueva.')
            ->assertRedirect();

        $this->page->refresh();
        $this->assertStringContainsString('Cambio sin guardar.', (string) $this->page->content);
        $this->assertNull($this->page->locked_by_user_id);

        $created = ContentPage::query()->where('slug', 'nueva-seccion-ia')->firstOrFail();
        $this->assertSame('Nueva sección IA', $created->title);
        $this->assertSame(2, (int) $created->order);
        $this->assertStringContainsString('Cuerpo de la página nueva.', (string) $created->content);
    }

    #[Test]
    public function a_new_page_that_cannot_be_saved_is_not_left_empty(): void
    {
        // Un Editor no puede guardar bloques de HTML libre: el texto generado
        // traía uno.
        $editor = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);
        app(ContentContributorService::class)->add($this->content, $editor);
        app(ContentPageFormatService::class)->save($this->page, ContentPageFormatEnum::EditorJs, $this->editorJs([['id' => 'p1', 'type' => 'paragraph', 'data' => ['text' => 'Hola']]]));

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($editor)
            ->test(ManageContentPages::class, ['record' => $this->content->id, 'page' => $this->page->id])
            ->call('applyAiNewPage', 'Con HTML', 'con-html', $this->editorJs([['id' => 'r1', 'type' => 'raw', 'data' => ['html' => '<details>Oculto</details>']]]))
            ->assertNoRedirect();

        $this->assertSame(1, ContentPage::query()->count());
        $this->assertSame($editor->id, (int) $this->page->refresh()->locked_by_user_id, 'Sigue con su página bloqueada.');
    }

    #[Test]
    public function the_modal_offers_the_usable_providers_and_no_reasoning_level_for_mistral(): void
    {
        AiProvider::factory()->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5')->create();
        AiProvider::factory()->provider(AiProviderEnum::OpenAi, 'gpt-apagado')->create(['is_active' => false]);

        $component = $this->editor($this->page)->mountAction('aiAssistant');
        $providers = $component->instance()->getMountedAction()->getModalContent()->getData()['providers'];

        // El predeterminado primero; el inactivo no sale. Mistral sólo razona o
        // no: el nivel no se elige (y `low` le da 400).
        $this->assertSame(
            [['gemini', true], ['mistral', false]],
            array_map(fn (array $provider): array => [$provider['value'], $provider['reasoning']], $providers),
        );
    }

    #[Test]
    public function livewire_ai_assistant_action_is_hidden_without_a_usable_provider(): void
    {
        $this->gemini->update(['is_active' => false]);

        $this->editor($this->page)->assertActionHidden('aiAssistant');
    }

    // ── Ayudantes ───────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>|string  $body
     */
    private function fakeGemini(array|string $body, int $status = 200): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($body, $status, is_string($body) ? ['Content-Type' => 'text/event-stream'] : []),
        ]);
    }

    /**
     * @param  list<string>  $chunks
     */
    private function sse(array $chunks): string
    {
        return implode('', array_map(
            fn (string $chunk): string => 'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => $chunk]]]]]])."\n\n",
            $chunks,
        ));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'page_id' => $this->page->id,
            'mode' => AiModeEnum::Edit->value,
            'provider' => AiProviderEnum::Gemini->value,
            'reasoning_level' => AiReasoningLevelEnum::Medium->value,
            'prompt' => 'Añade una conclusión',
            'current_content' => 'Texto anterior',
            'source_format' => ContentPageFormatEnum::Markdown->value,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function stream(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.contents.pages.ai.stream', $this->content), $this->payload($overrides));
    }

    private function editor(ContentPage|string $page): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($this->admin)->test(ManageContentPages::class, [
            'record' => $this->content->id,
            'page' => $page instanceof ContentPage ? $page->id : $page,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function editorJs(array $blocks): string
    {
        return (string) json_encode(['time' => 1, 'version' => '2.31.7', 'blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
