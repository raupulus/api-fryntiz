<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Enums\AiProviderEnum;
use App\Models\AiProvider;
use App\Services\Ai\AiImageException;
use App\Services\Ai\GeneratedImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los conectores de imágenes, con respuestas simuladas con la forma de la
 * documentación de cada uno (2026-10-17). Gemini y Mistral, además, con la de
 * una llamada real de ese día.
 */
class AiImageProvidersTest extends TestCase
{
    use RefreshDatabase;

    // ── Gemini ──────────────────────────────────────────────────────────────

    #[Test]
    public function gemini_asks_for_one_image_per_request_at_the_same_time_with_the_key_in_a_header(): void
    {
        $provider = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');
        $this->fake(['generativelanguage.googleapis.com/*' => Http::response($this->gemini($this->png(16, 9)))]);

        $images = $provider->imageConnector()->generate($provider, 'Un pingüino', 3);

        $this->assertCount(3, $images);
        $this->assertSame(['image/png', 16, 9], [$images[0]->mime, $images[0]->width, $images[0]->height]);
        Http::assertSentCount(3);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-nano-banana-2.1:generateContent'
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
            && ! str_contains($request->url(), 'test-gemini-key')
            && $request['contents'] === [['role' => 'user', 'parts' => [['text' => 'Un pingüino']]]]
            && $request['generationConfig'] === ['responseModalities' => ['IMAGE'], 'imageConfig' => ['aspectRatio' => '16:9', 'imageSize' => '2K']]);
    }

    #[Test]
    public function gemini_keeps_the_last_image_that_is_not_a_thought_and_sends_the_one_to_improve_inline(): void
    {
        $provider = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');
        $draft = base64_encode($this->png(4, 4));
        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [
            ['inlineData' => ['mimeType' => 'image/png', 'data' => $draft], 'thought' => true],
            ['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode($this->png(32, 18))]],
        ]], 'finishReason' => 'STOP']]])]);

        $source = GeneratedImage::fromBytes($this->png(8, 8));
        $images = $provider->imageConnector()->edit($provider, $source, 'Más luz', 1, '1K');

        $this->assertSame(32, $images[0]->width, 'La del pensamiento era un borrador.');
        Http::assertSent(fn (HttpRequest $request): bool => $request['contents'][0]['parts'] === [
            ['inlineData' => ['mimeType' => 'image/png', 'data' => $source->base64()]],
            ['text' => 'Más luz'],
        ] && $request['generationConfig']['imageConfig']['imageSize'] === '1K');
    }

    #[Test]
    public function gemini_filters_are_said_as_such(): void
    {
        $provider = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');

        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => []], 'finishReason' => 'IMAGE_SAFETY']]])]);
        $this->assertFailsWith('no pasa sus filtros de contenido', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));

        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['promptFeedback' => ['blockReason' => 'SAFETY']])]);
        $this->assertFailsWith('no pasa sus filtros de contenido', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));

        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'No puedo']]], 'finishReason' => 'NO_IMAGE']]])]);
        $this->assertFailsWith('Gemini no ha devuelto ninguna imagen (NO_IMAGE)', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
    }

    #[Test]
    public function if_some_requests_fail_the_images_that_came_are_kept(): void
    {
        $provider = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');
        $this->fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->gemini($this->png(16, 9)))
            ->push(['error' => ['message' => 'Internal error']], 500)
            ->push($this->gemini($this->png(16, 9)))]);

        $this->assertCount(2, $provider->imageConnector()->generate($provider, 'Algo', 3));

        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Your prepayment credits are depleted.']], 402)]);
        $this->assertFailsWith('no tiene saldo', fn () => $provider->imageConnector()->generate($provider, 'Algo', 2));

        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'models/no-existe is not found']], 404)]);
        $this->assertFailsWith('El modelo gemini-nano-banana-2.1 no está disponible', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
    }

    // ── OpenAI ──────────────────────────────────────────────────────────────

    #[Test]
    public function openai_asks_for_all_the_images_in_one_request_and_improves_with_edits(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-image-test');
        $this->fake(['api.openai.com/*' => Http::response(['data' => [['b64_json' => base64_encode($this->png(30, 20))], ['b64_json' => base64_encode($this->png(30, 20))]]])]);

        $this->assertCount(2, $provider->imageConnector()->generate($provider, 'Un pingüino', 2));

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.openai.com/v1/images/generations'
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && $request->data() === ['model' => 'gpt-image-test', 'prompt' => 'Un pingüino', 'n' => 2, 'size' => '1536x1024', 'quality' => 'medium', 'output_format' => 'png']);

        $source = GeneratedImage::fromBytes($this->png(8, 8));
        $provider->imageConnector()->edit($provider, $source, 'Más luz', 1, 'high');

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.openai.com/v1/images/edits'
            && $request['images'] === [['image_url' => $source->dataUri()]]
            && $request['quality'] === 'high');
    }

    #[Test]
    public function openai_moderation_is_said_as_a_filter(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-image-test');
        $this->fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Your request was rejected by the safety system.', 'code' => 'moderation_blocked']], 400)]);

        $this->assertFailsWith('OpenAI no ha querido hacer esta imagen', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
    }

    // ── Grok (xAI) ──────────────────────────────────────────────────────────

    #[Test]
    public function grok_asks_for_base64_in_16_9_and_sends_the_image_to_improve_as_a_data_uri(): void
    {
        $provider = $this->provider(AiProviderEnum::Xai, 'grok-imagine-image-2.0');
        $this->fake(['api.x.ai/*' => Http::response(['data' => [['b64_json' => base64_encode($this->png(16, 9)), 'mime_type' => 'image/png']]])]);

        $provider->imageConnector()->generate($provider, 'Un pingüino', 1);

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.x.ai/v1/images/generations'
            && $request->hasHeader('Authorization', 'Bearer test-xai-key')
            && $request->data() === ['model' => 'grok-imagine-image-2.0', 'prompt' => 'Un pingüino', 'n' => 1, 'response_format' => 'b64_json', 'aspect_ratio' => '16:9', 'resolution' => '2k']);

        $source = GeneratedImage::fromBytes($this->png(8, 8));
        $provider->imageConnector()->edit($provider, $source, 'Más luz', 1, '1k');

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.x.ai/v1/images/edits'
            && $request['image'] === ['url' => $source->dataUri()]
            && $request['resolution'] === '1k');
    }

    #[Test]
    public function grok_without_credit_says_so(): void
    {
        $provider = $this->provider(AiProviderEnum::Xai, 'grok-imagine-image-2.0');
        $this->fake(['api.x.ai/*' => Http::response(['code' => 'forbidden', 'error' => 'Your team has either used all available credits or reached its monthly spending limit.'], 403)]);

        $this->assertFailsWith('no tiene saldo', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
    }

    // ── Mistral ─────────────────────────────────────────────────────────────

    #[Test]
    public function mistral_uses_its_image_tool_and_downloads_the_image_from_the_signed_url(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');
        $this->fake([
            'api.mistral.ai/*' => Http::response($this->mistral('https://mistralaiblackforestprod.blob.core.windows.net/images/x.jpg?sig=abc')),
            'mistralaiblackforestprod.blob.core.windows.net/*' => Http::response($this->png(40, 30)),
        ]);

        $images = $provider->imageConnector()->generate($provider, 'Un pingüino', 2);

        $this->assertCount(2, $images);
        $this->assertSame(40, $images[0]->width);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.mistral.ai/v1/conversations'
            && $request->hasHeader('Authorization', 'Bearer test-mistral-key')
            && $request['model'] === 'mistral-medium-3-5'
            && $request['tools'] === [['type' => 'image_generation']]
            && $request['store'] === false
            && str_ends_with($request['inputs'], 'Un pingüino'));
        Http::assertSentCount(4);
    }

    #[Test]
    public function mistral_also_takes_a_tool_file_and_only_downloads_from_its_own_hosts(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');
        $this->fake([
            'api.mistral.ai/v1/conversations' => Http::response(['outputs' => [['type' => 'message.output', 'content' => [['type' => 'tool_file', 'tool' => 'image_generation', 'file_id' => 'file-1', 'file_type' => 'png']]]]]),
            'api.mistral.ai/v1/files/file-1/content' => Http::response($this->png(20, 10)),
        ]);

        $this->assertSame(20, $provider->imageConnector()->generate($provider, 'Algo', 1)[0]->width);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.mistral.ai/v1/files/file-1/content'
            && $request->hasHeader('Authorization', 'Bearer test-mistral-key'));

        foreach (['https://example.com/x.jpg', 'http://mistralaiblackforestprod.blob.core.windows.net/x.jpg'] as $url) {
            $this->fake(['api.mistral.ai/*' => Http::response($this->mistral($url)), '*' => Http::response($this->png(4, 4))]);
            $this->assertFailsWith('no se descarga', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
            Http::assertSentCount(1);
        }
    }

    #[Test]
    public function mistral_says_what_it_answered_when_it_draws_nothing_and_cannot_improve_an_image(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');
        $this->fake(['api.mistral.ai/*' => Http::response(['outputs' => [['type' => 'message.output', 'content' => 'No puedo generar esa imagen.']]])]);

        $this->assertFailsWith('Mistral no ha generado ninguna imagen: «No puedo generar esa imagen.»', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));

        $this->fake([]);
        $this->assertFailsWith('sólo las genera', fn () => $provider->imageConnector()->edit($provider, GeneratedImage::fromBytes($this->png(4, 4)), 'Algo', 1));
        Http::assertNothingSent();
    }

    // ── MiniMax ─────────────────────────────────────────────────────────────

    #[Test]
    public function minimax_asks_for_images_with_aspect_ratio_16_9_and_base64_response_format(): void
    {
        $provider = $this->provider(AiProviderEnum::MiniMax, 'image-01');
        $this->fake(['api.minimax.io/*' => Http::response([
            'data' => [
                'image_base64' => [
                    base64_encode($this->png(16, 9)),
                    base64_encode($this->png(16, 9)),
                ],
            ],
            'base_resp' => ['status_code' => 0, 'status_msg' => 'success'],
        ])]);

        $images = $provider->imageConnector()->generate($provider, 'Un pingüino', 2);

        $this->assertCount(2, $images);
        $this->assertSame(['image/png', 16, 9], [$images[0]->mime, $images[0]->width, $images[0]->height]);
        Http::assertSentCount(1);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.minimax.io/v1/image_generation'
            && $request->hasHeader('Authorization', 'Bearer test-minimax-key')
            && ! str_contains($request->url(), 'test-minimax-key')
            && $request->data() === [
                'model' => 'image-01',
                'prompt' => 'Un pingüino',
                'aspect_ratio' => '16:9',
                'response_format' => 'base64',
                'n' => 2,
            ]);
    }

    #[Test]
    public function minimax_image_editing_sends_subject_reference_with_data_uri(): void
    {
        $provider = $this->provider(AiProviderEnum::MiniMax, 'image-01');
        $this->fake(['api.minimax.io/*' => Http::response([
            'data' => ['image_base64' => [base64_encode($this->png(16, 9))]],
        ])]);

        $source = GeneratedImage::fromBytes($this->png(8, 8));
        $images = $provider->imageConnector()->edit($provider, $source, 'Más luz', 1);

        $this->assertCount(1, $images);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.minimax.io/v1/image_generation'
            && $request['subject_reference'] === [
                [
                    'type' => 'character',
                    'image_file' => $source->dataUri(),
                ],
            ]);
    }

    #[Test]
    public function minimax_insufficient_balance_in_base_resp_is_reported(): void
    {
        $provider = $this->provider(AiProviderEnum::MiniMax, 'image-01');
        $this->fake(['api.minimax.io/*' => Http::response([
            'base_resp' => ['status_code' => 1008, 'status_msg' => 'insufficient balance'],
        ])]);

        $this->assertFailsWith('no tiene saldo', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
    }

    #[Test]
    public function minimax_sensitive_filter_is_reported(): void
    {
        $provider = $this->provider(AiProviderEnum::MiniMax, 'image-01');
        $this->fake(['api.minimax.io/*' => Http::response([
            'base_resp' => ['status_code' => 1026, 'status_msg' => 'input sensitive'],
        ])]);

        $this->assertFailsWith('no pasa sus filtros de contenido', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));
    }

    // ── Comunes ─────────────────────────────────────────────────────────────

    #[Test]
    public function without_an_image_model_or_a_key_nothing_is_sent(): void
    {
        $provider = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');
        $this->fake([]);

        $provider->image_model = null;
        $this->assertFailsWith('no tiene modelo de imágenes', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));

        $provider->image_model = 'gemini-nano-banana-2.1';
        $provider->api_key = null;
        $this->assertFailsWith('no está puesta o no se puede leer', fn () => $provider->imageConnector()->generate($provider, 'Algo', 1));

        Http::assertNothingSent();
    }

    #[Test]
    public function the_probe_generates_one_image_with_the_cheapest_quality(): void
    {
        $provider = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');
        $this->fake(['generativelanguage.googleapis.com/*' => Http::response($this->gemini($this->png(16, 9)))]);

        $this->assertSame(['ok' => true, 'message' => 'Ha generado una imagen de 16 × 9.'], $provider->imageConnector()->probe($provider));
        Http::assertSent(fn (HttpRequest $request): bool => $request['generationConfig']['imageConfig']['imageSize'] === '1K');

        $this->fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid.']], 400)]);
        $this->assertFalse($provider->imageConnector()->probe($provider)['ok']);
    }

    #[Test]
    public function only_providers_with_an_image_model_can_make_images(): void
    {
        $gemini = $this->provider(AiProviderEnum::Gemini, 'gemini-nano-banana-2.1');
        $this->provider(AiProviderEnum::OpenAi, 'gpt-image-test', ['image_model' => null]);
        $this->provider(AiProviderEnum::Xai, 'grok', ['image_model' => 'grok-imagine-image-2.0', 'is_active' => false]);

        $this->assertSame([$gemini->id], AiProvider::usableForImages()->modelKeys());
        $this->assertSame('Gemini · gemini-nano-banana-2.1', $gemini->imageLabel());
    }

    // ── Ayudantes ───────────────────────────────────────────────────────────

    /**
     * Cada llamada empieza de cero: `Http::fake()` añade sus respuestas detrás
     * de las anteriores.
     *
     * @param  array<string, mixed>  $responses
     */
    private function fake(array $responses): void
    {
        Http::swap(new Factory(app('events')));
        Http::fake($responses === [] ? fn () => Http::response('nada', 500) : $responses);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function provider(AiProviderEnum $provider, string $imageModel, array $attributes = []): AiProvider
    {
        return AiProvider::factory()->provider($provider, 'modelo-de-texto')->create(['image_model' => $imageModel, ...$attributes]);
    }

    private function png(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, mixed>
     */
    private function gemini(string $bytes): array
    {
        return ['candidates' => [['content' => ['parts' => [['inlineData' => ['mimeType' => 'image/png', 'data' => base64_encode($bytes)]]]], 'finishReason' => 'STOP']]];
    }

    /**
     * Como respondió la API real el 2026-10-17.
     *
     * @return array<string, mixed>
     */
    private function mistral(string $url): array
    {
        return ['object' => 'conversation.response', 'outputs' => [
            ['object' => 'entry', 'type' => 'tool.execution', 'name' => 'image_generation', 'info' => ['result' => json_encode(['url' => $url])]],
            ['object' => 'entry', 'type' => 'message.output', 'role' => 'assistant', 'content' => 'Aquí tienes la imagen.'],
        ]];
    }

    private function assertFailsWith(string $message, callable $call): void
    {
        try {
            $call();
        } catch (AiImageException $e) {
            $this->assertStringContainsString($message, $e->getMessage());

            return;
        }

        $this->fail("Tenía que fallar con «{$message}».");
    }
}
