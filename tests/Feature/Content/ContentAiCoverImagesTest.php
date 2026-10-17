<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Enums\AiProviderEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\CreateContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\ManageContentPages;
use App\Models\AiProvider;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\File;
use App\Models\Platform;
use App\Models\User;
use App\Services\Content\ContentAiImageService;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;
use Tests\Traits\UsesTemporaryStorage;

/**
 * Portadas con IA: generar o mejorar la de la entrada y la de una página,
 * elegir una y guardarla por el camino de siempre (D53).
 */
class ContentAiCoverImagesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;
    use UsesTemporaryStorage;

    private User $admin;

    private Platform $platform;

    private Content $content;

    private AiProvider $gemini;

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
        $this->platform = Platform::factory()->create([
            'title' => 'La Guía Linux',
            'theme_colors' => [['hex' => '#0F172A', 'role' => 'background'], ['hex' => '#22C55E', 'role' => 'accent']],
        ]);
        $this->content = Content::factory()->draft()->create([
            'platform_id' => $this->platform->id,
            'author_id' => $this->admin->id,
            'title' => 'Instalar Debian',
            'excerpt' => 'Paso a paso',
        ]);
        $this->gemini = AiProvider::factory()->create(['image_model' => 'gemini-nano-banana-2.1']);
    }

    // ── La petición ─────────────────────────────────────────────────────────

    #[Test]
    public function generating_streams_the_images_cropped_to_16_9_as_tokens_with_a_preview(): void
    {
        $this->fakeGemini(1600, 1200);

        $body = $this->images(['count' => 2])->assertOk()->streamedContent();

        $images = $this->events($body, 'image');
        $this->assertCount(2, $images);
        $this->assertSame([1600, 900], [$images[0]['width'], $images[0]['height']], 'Recortada al centro a 16:9.');
        $this->assertSame(['url', 'width', 'height'], array_keys($images[0]));
        $this->assertStringContainsString('livewire', $images[0]['url'], 'La vista previa firmada de la subida temporal.');

        // La vista previa se puede descargar: es lo que hace el navegador al elegirla.
        $this->get($images[0]['url'])->assertOk();
        $this->assertCount(1, $this->events($body, 'done'));
        Http::assertSentCount(2);
    }

    #[Test]
    public function the_hidden_prompt_has_the_context_and_the_colors_only_when_asked(): void
    {
        $this->fakeGemini();

        $this->images(['prompt' => 'Un portátil con una terminal', 'use_colors' => true])->streamedContent();

        Http::assertSent(function (HttpRequest $request): bool {
            $text = $request['contents'][0]['parts'][0]['text'];

            return str_contains($text, 'Artículo: «Instalar Debian»')
                && str_contains($text, 'Petición: Un portátil con una terminal')
                && str_contains($text, 'Sin texto, letras')
                && str_contains($text, '#0F172A (fondo), #22C55E (acento)')
                && str_contains($text, 'cuida el contraste');
        });

        $this->fakeGemini();
        $this->images(['use_colors' => false])->streamedContent();

        Http::assertSent(fn (HttpRequest $request): bool => ! str_contains($request['contents'][0]['parts'][0]['text'], '#0F172A')
            && str_contains($request['contents'][0]['parts'][0]['text'], 'ilustra el tema del artículo'));
    }

    #[Test]
    public function only_providers_with_an_image_model_and_their_own_options_are_accepted(): void
    {
        AiProvider::factory()->provider(AiProviderEnum::OpenAi, 'gpt-5')->create();
        $this->fakeGemini();

        $this->images(['provider' => 'openai'])->assertSessionHasErrors('provider');
        $this->images(['count' => 6])->assertSessionHasErrors('count');
        $this->images(['count' => 0])->assertSessionHasErrors('count');
        $this->images(['quality' => 'high'])->assertSessionHasErrors('quality');
        $this->images(['mode' => 'improve'])->assertSessionHasErrors('source');
        $this->images(['mode' => 'improve', 'source' => str_repeat('a', 40)])->assertSessionHasErrors('source');
        $this->images(['page_id' => ContentPage::query()->create(['content_id' => Content::factory()->create()->id, 'title' => 'Ajena', 'slug' => 'ajena', 'order' => 1])->id])->assertNotFound();

        Http::assertNothingSent();
    }

    #[Test]
    public function mistral_cannot_improve_an_image(): void
    {
        AiProvider::factory()->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5')->create(['image_model' => 'mistral-medium-3-5']);
        $token = app(ContentAiImageService::class)->registerSource($this->cover(), $this->content, $this->admin->id);

        $this->images(['mode' => 'improve', 'provider' => 'mistral', 'quality' => null, 'source' => $token])->assertSessionHasErrors('provider');
    }

    #[Test]
    public function improving_sends_the_saved_cover_and_a_token_of_someone_else_is_not_accepted(): void
    {
        $this->fakeGemini();
        $cover = $this->cover();
        $service = app(ContentAiImageService::class);

        $this->images(['mode' => 'improve', 'source' => $service->registerSource($cover, $this->content, $this->admin->id), 'prompt' => 'Más luz'])
            ->assertOk()->streamedContent();

        Http::assertSent(fn (HttpRequest $request): bool => $request['contents'][0]['parts'][0]['inlineData']['mimeType'] === 'image/jpeg'
            && str_contains($request['contents'][0]['parts'][1]['text'], 'Mejora esta imagen')
            && str_contains($request['contents'][0]['parts'][1]['text'], 'Petición: Más luz'));

        $other = User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]);
        $this->images(['mode' => 'improve', 'source' => $service->registerSource($cover, $this->content, $other->id)])->assertSessionHasErrors('source');
        $this->images(['mode' => 'improve', 'source' => $service->registerSource($cover, Content::factory()->create(), $this->admin->id)])->assertSessionHasErrors('source');
    }

    #[Test]
    public function an_error_of_the_provider_ends_with_only_an_error(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Your prepayment credits are depleted.']], 402)]);

        $body = $this->images()->streamedContent();

        $this->assertStringContainsString('no tiene saldo', $this->events($body, 'error')[0]['message']);
        $this->assertSame([], $this->events($body, 'image'));
        $this->assertSame([], $this->events($body, 'done'));
    }

    // ── Elegir y guardar ────────────────────────────────────────────────────
    //
    // Al elegir una, el navegador apunta cómo estaba el campo
    // (`rememberAiCover`) y le da la imagen como una subida más, que sustituye
    // a la que hubiera: aquí, vaciar el campo y subir un fichero.

    #[Test]
    public function the_chosen_image_enters_the_content_cover_as_an_upload_and_saving_stores_it_as_webp(): void
    {
        $previous = $this->cover();
        $this->content->update(['image_id' => $previous->id]);

        $component = $this->editContent()
            ->call('rememberAiCover')
            ->assertNotified('Portada puesta, sin guardar')
            ->assertActionVisible(TestAction::make('aiCoverUndo')->schemaComponent('image_id'))
            ->set('data.image_id', [])
            ->fillForm(['image_id' => $this->upload()]);

        $this->assertSame($previous->id, (int) $this->content->refresh()->image_id, 'Sin guardar no cambia nada.');

        $component->call('save')->assertHasNoFormErrors()
            ->assertActionDoesNotExist(TestAction::make('aiCoverUndo')->schemaComponent('image_id'));

        $cover = File::query()->findOrFail($this->content->refresh()->image_id);
        $this->assertNotSame($previous->id, $cover->id);
        $this->assertSame('image/webp', $cover->fileType?->mime, 'Por el camino de siempre (D40).');
    }

    #[Test]
    public function undo_gives_back_the_field_as_it_was_before_the_first_ai_image(): void
    {
        $this->content->update(['image_id' => $this->cover()->id]);

        $component = $this->editContent();
        $before = $component->get('data.image_id');

        $component->call('rememberAiCover')
            ->set('data.image_id', [])
            ->fillForm(['image_id' => $this->upload()])
            ->call('rememberAiCover')
            ->set('data.image_id', [])
            ->fillForm(['image_id' => $this->upload()])
            ->assertActionVisible(TestAction::make('aiCoverUndo')->schemaComponent('image_id'))
            ->call('takeAiCoverUndo')
            ->assertReturned($before)
            ->assertSet('aiCoverUndo', [])
            ->assertNotified('Portada como estaba');
    }

    #[Test]
    public function only_who_can_edit_the_content_can_ask_for_images(): void
    {
        $outsider = User::factory()->create(['role_id' => UserRoleEnum::Editor->value, 'is_active' => true]);

        $this->actingAs($outsider)->post(route('admin.contents.ai.images', $this->content), ['mode' => 'generate', 'provider' => 'gemini', 'count' => 1])
            ->assertForbidden();
    }

    #[Test]
    public function a_page_cover_can_be_chosen_and_saved_from_the_page_editor(): void
    {
        $page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Requisitos', 'slug' => 'requisitos', 'order' => 1]);

        Livewire::actingAs($this->admin)
            ->test(ManageContentPages::class, ['record' => $this->content->id, 'page' => $page->id])
            ->assertActionVisible(TestAction::make('aiCoverGenerate')->schemaComponent('image_id'))
            ->assertActionDoesNotExist(TestAction::make('aiCoverImprove')->schemaComponent('image_id'))
            ->call('rememberAiCover')
            ->assertActionVisible(TestAction::make('aiCoverUndo')->schemaComponent('image_id'))
            ->fillForm(['image_id' => $this->upload()])
            ->call('save');

        $this->assertSame('image/webp', File::query()->findOrFail($page->refresh()->image_id)->fileType?->mime);
    }

    // ── Cuándo sale ─────────────────────────────────────────────────────────
    //
    // Una acción oculta de un campo no se puede resolver: para Filament no
    // existe (`assertActionDoesNotExist`).

    #[Test]
    public function the_actions_only_show_when_they_can_be_used(): void
    {
        $this->editContent()
            ->assertActionVisible(TestAction::make('aiCoverGenerate')->schemaComponent('image_id'))
            ->assertActionDoesNotExist(TestAction::make('aiCoverImprove')->schemaComponent('image_id'))
            ->assertActionDoesNotExist(TestAction::make('aiCoverUndo')->schemaComponent('image_id'));

        $this->content->update(['image_id' => $this->cover()->id]);
        $this->editContent()->assertActionVisible(TestAction::make('aiCoverImprove')->schemaComponent('image_id'));

        Livewire::actingAs($this->admin)->test(CreateContent::class)
            ->assertActionDoesNotExist(TestAction::make('aiCoverGenerate')->schemaComponent('image_id'));

        $this->gemini->update(['image_model' => null]);
        $this->editContent()->assertActionDoesNotExist(TestAction::make('aiCoverGenerate')->schemaComponent('image_id'));
    }

    #[Test]
    public function the_modal_offers_the_providers_with_their_qualities_and_the_platform_colors(): void
    {
        AiProvider::factory()->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5')->create(['image_model' => 'mistral-medium-3-5']);

        $generate = $this->editContent()->instance()->aiCoverModalData('generate');

        $this->assertSame(['gemini', 'mistral'], array_column($generate['providers'], 'value'));
        $this->assertSame(['1K', '2K'], array_column($generate['providers'][0]['qualities'], 'value'));
        $this->assertSame('2K', $generate['providers'][0]['defaultQuality']);
        $this->assertSame(['#0F172A', '#22C55E'], array_column($generate['colors'], 'hex'));
        $this->assertNull($generate['sourceToken']);

        $this->content->update(['image_id' => $this->cover()->id]);
        $improve = $this->editContent()->instance()->aiCoverModalData('improve');

        $this->assertSame(['gemini'], array_column($improve['providers'], 'value'), 'Mistral no sabe mejorar.');
        $this->assertNotNull($improve['sourceToken']);
    }

    // ── Ayudantes ───────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function images(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.contents.ai.images', $this->content), [
            'mode' => 'generate',
            'provider' => 'gemini',
            'count' => 1,
            'quality' => '1K',
            'use_colors' => false,
            'prompt' => '',
            ...$overrides,
        ]);
    }

    private function fakeGemini(int $width = 32, int $height = 18): void
    {
        Http::swap(new Factory(app('events')));
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [
            ['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode($this->png($width, $height))]],
        ]], 'finishReason' => 'STOP']]])]);
    }

    private function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 30, 120, 200));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(string $body, string $type): array
    {
        preg_match_all('/^event: '.$type."\ndata: (.+)$/m", $body, $matches);

        return array_map(fn (string $json): array => json_decode($json, true), $matches[1]);
    }

    private function cover(): File
    {
        return File::addFile(UploadedFile::fake()->image('portada.jpg', 1600, 900), 'contents', false);
    }

    private function upload(): UploadedFile
    {
        return UploadedFile::fake()->image('portada-ia.jpg', 1600, 900);
    }

    private function editContent(): Testable
    {
        return Livewire::actingAs($this->admin)->test(EditContent::class, ['record' => $this->content->getRouteKey()]);
    }
}
