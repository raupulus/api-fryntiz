<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Enums\AiProviderEnum;
use App\Enums\AiReasoningLevelEnum;
use App\Models\AiProvider;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los conectores de Mistral y de la API Responses (OpenAI y Grok), con
 * respuestas simuladas. Gemini se prueba en `ContentPageAiAssistantTest`.
 *
 * Los eventos son los de la documentación de cada uno (2026-10-17): Responses
 * manda `type` en cada `data:`; Mistral manda `choices[0].delta.content`,
 * texto o lista de trozos `thinking`/`text`, y cierra con `[DONE]`.
 */
class AiTextProvidersTest extends TestCase
{
    use RefreshDatabase;

    // ── API Responses (OpenAI y Grok) ───────────────────────────────────────

    #[Test]
    public function openai_gets_the_responses_api_with_the_key_in_a_bearer_header(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-test');
        $this->fake('api.openai.com', $this->sseResponse([['type' => 'response.output_text.delta', 'delta' => 'Hola']]));

        $this->collect($provider, AiReasoningLevelEnum::High);

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && ! str_contains($request->url(), 'test-openai-key')
            && $request['model'] === 'gpt-test'
            && $request['input'] === [['role' => 'system', 'content' => 'Sistema'], ['role' => 'user', 'content' => 'Pregunta']]
            && $request['stream'] === true
            && $request['store'] === false
            && $request['reasoning'] === ['effort' => 'high', 'summary' => 'auto']);
    }

    #[Test]
    public function grok_uses_the_xai_responses_api_and_does_not_ask_for_a_summary_by_default(): void
    {
        $provider = $this->provider(AiProviderEnum::Xai, 'grok-4.7');
        $this->fake('api.x.ai', $this->sseResponse([['type' => 'response.output_text.delta', 'delta' => 'Hola']]));

        $this->collect($provider, AiReasoningLevelEnum::Low);

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.x.ai/v1/responses'
            && $request['reasoning'] === ['effort' => 'low']);
    }

    #[Test]
    public function without_reasoning_options_nothing_about_reasoning_is_sent(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-test', ['sends_reasoning_level' => false, 'requests_reasoning_summary' => false]);
        $this->fake('api.openai.com', $this->sseResponse([['type' => 'response.output_text.delta', 'delta' => 'Hola']]));

        $this->collect($provider, AiReasoningLevelEnum::High);

        Http::assertSent(fn (HttpRequest $request): bool => ! isset($request['reasoning']));
    }

    #[Test]
    public function the_responses_stream_turns_into_thoughts_and_content(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-test');
        $this->fake('api.openai.com', $this->sseResponse([
            ['type' => 'response.created', 'response' => ['id' => 'r1']],
            ['type' => 'response.reasoning_summary_part.added', 'summary_index' => 0],
            ['type' => 'response.reasoning_summary_text.delta', 'delta' => 'Pienso'],
            ['type' => 'response.reasoning_summary_part.added', 'summary_index' => 1],
            ['type' => 'response.reasoning_summary_text.delta', 'delta' => 'Sigo'],
            ['type' => 'response.output_text.delta', 'delta' => 'Hola, '],
            ['type' => 'response.output_text.delta', 'delta' => 'mundo'],
            ['type' => 'response.completed', 'response' => ['status' => 'completed']],
        ], named: true));

        $events = $this->collect($provider);

        $this->assertSame("Pienso\n\nSigo", $this->text($events, 'thought'));
        $this->assertSame('Hola, mundo', $this->text($events, 'content'));
        $this->assertSame([], $this->of($events, 'error'));
    }

    #[Test]
    public function a_cut_responses_answer_warns_and_a_failed_one_is_an_error(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-test');
        $this->fake('api.openai.com', $this->sseResponse([
            ['type' => 'response.output_text.delta', 'delta' => 'A medias'],
            ['type' => 'response.incomplete', 'response' => ['incomplete_details' => ['reason' => 'max_output_tokens']]],
        ]));

        $this->assertStringContainsString('OpenAI no ha terminado: la respuesta se ha cortado por larga', implode(' ', $this->of($this->collect($provider), 'warning')));

        $this->fake('api.openai.com', $this->sseResponse([
            ['type' => 'response.failed', 'response' => ['error' => ['code' => 'server_error', 'message' => 'Algo ha ido mal']]],
            ['type' => 'response.output_text.delta', 'delta' => 'No debe llegar'],
        ]));

        $events = $this->collect($provider);
        $this->assertSame(['OpenAI no ha podido terminar la respuesta: Algo ha ido mal'], $this->of($events, 'error'));
        $this->assertSame('', $this->text($events, 'content'));
    }

    #[Test]
    public function a_refusal_is_an_error(): void
    {
        $provider = $this->provider(AiProviderEnum::Xai, 'grok-4.7');
        $this->fake('api.x.ai', $this->sseResponse([
            ['type' => 'response.refusal.delta', 'delta' => 'No puedo '],
            ['type' => 'response.refusal.delta', 'delta' => 'ayudar con eso.'],
            ['type' => 'response.refusal.done'],
        ]));

        $this->assertSame(['Grok se ha negado a hacerlo: No puedo ayudar con eso.'], $this->of($this->collect($provider), 'error'));
    }

    #[Test]
    public function http_errors_say_what_happened(): void
    {
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-test');

        $this->fake('api.openai.com', Http::response(['error' => ['message' => 'You exceeded your current quota.', 'code' => 'insufficient_quota']], 429));
        $this->assertStringContainsString('La cuenta de OpenAI no tiene saldo', $this->of($this->collect($provider), 'error')[0]);

        $this->fake('api.openai.com', Http::response(['error' => ['message' => 'Rate limit reached.', 'code' => 'rate_limit_exceeded']], 429));
        $this->assertStringContainsString('Se ha agotado la cuota de OpenAI', $this->of($this->collect($provider), 'error')[0]);

        // xAI escribe el error como texto, no como objeto.
        $grok = $this->provider(AiProviderEnum::Xai, 'grok-4.7');
        $this->fake('api.x.ai', Http::response(['code' => 'Client specified an invalid argument', 'error' => 'Incorrect API key provided.'], 400));
        $this->assertSame('Grok ha rechazado la petición (400): Incorrect API key provided.', $this->of($this->collect($grok), 'error')[0]);

        $this->fake('api.x.ai', Http::response(['error' => 'Unauthorized'], 401));
        $this->assertSame('La clave de Grok no vale o no tiene permiso para usar la API (Unauthorized).', $this->of($this->collect($grok), 'error')[0]);

        // Sin saldo, xAI responde 403: no es que la clave no valga (probado
        // con la API real el 2026-10-17).
        $this->fake('api.x.ai', Http::response(['code' => 'The caller does not have permission to execute the specified operation', 'error' => 'Your team abc has either used all available credits or reached its monthly spending limit.'], 403));
        $this->assertStringStartsWith('La cuenta de Grok no tiene saldo o ha llegado a su límite de gasto', $this->of($this->collect($grok), 'error')[0]);
    }

    // ── Mistral ─────────────────────────────────────────────────────────────

    #[Test]
    public function mistral_gets_chat_completions_and_reasons_with_high_whatever_the_level(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');
        $this->fake('api.mistral.ai', $this->sseResponse([$this->mistral('Hola')]));

        $this->collect($provider, AiReasoningLevelEnum::Medium);

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.mistral.ai/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-mistral-key')
            && $request['model'] === 'mistral-medium-3-5'
            && $request['messages'] === [['role' => 'system', 'content' => 'Sistema'], ['role' => 'user', 'content' => 'Pregunta']]
            && $request['stream'] === true
            // `mistral-medium-3-5` sólo admite `high` o `none`: con `low` da 400.
            && $request['reasoning_effort'] === 'high');
    }

    #[Test]
    public function mistral_thinking_chunks_are_thoughts_and_done_ends_the_stream(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');
        $this->fake('api.mistral.ai', $this->sseResponse([
            $this->mistral([['type' => 'thinking', 'thinking' => [['type' => 'text', 'text' => 'Razono']]]]),
            $this->mistral([['type' => 'thinking', 'thinking' => [['type' => 'text', 'text' => ' más']]], ['type' => 'text', 'text' => 'Hola']]),
            $this->mistral(', mundo'),
            $this->mistral('', 'stop'),
        ], done: true));

        $events = $this->collect($provider);

        $this->assertSame('Razono más', $this->text($events, 'thought'));
        $this->assertSame('Hola, mundo', $this->text($events, 'content'));
    }

    #[Test]
    public function a_cut_mistral_answer_warns(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');
        $this->fake('api.mistral.ai', $this->sseResponse([$this->mistral('A medias', 'length')]));

        $this->assertStringContainsString('se ha cortado por larga', implode(' ', $this->of($this->collect($provider), 'warning')));
    }

    // ── Prueba de conexión ──────────────────────────────────────────────────

    #[Test]
    public function the_probe_says_whether_the_model_answers(): void
    {
        $provider = $this->provider(AiProviderEnum::Mistral, 'mistral-medium-3-5');

        $this->fake('api.mistral.ai', $this->sseResponse([$this->mistral('OK')], done: true));
        $this->assertSame(['ok' => true, 'message' => 'OK'], $provider->connector()->probe($provider));

        $this->fake('api.mistral.ai', Http::response(['object' => 'error', 'message' => 'Invalid model: mistral-inventado', 'type' => 'invalid_model'], 400));
        $result = $provider->connector()->probe($provider);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Invalid model: mistral-inventado', $result['message']);
    }

    #[Test]
    public function without_a_readable_key_nothing_is_sent(): void
    {
        Http::fake();
        $provider = $this->provider(AiProviderEnum::OpenAi, 'gpt-test');
        $provider->newQuery()->whereKey($provider->id)->update(['api_key' => 'cifrada-con-otra-app-key']);

        $events = $this->collect($provider->refresh());

        $this->assertStringContainsString('no está puesta o no se puede leer', $this->of($events, 'error')[0]);
        Http::assertNothingSent();
    }

    // ── Ayudantes ───────────────────────────────────────────────────────────

    /**
     * Cada llamada empieza de cero: `Http::fake()` añade su respuesta detrás
     * de las anteriores, y ganaría la primera.
     */
    private function fake(string $host, PromiseInterface $response): void
    {
        Http::swap(new Factory(app('events')));
        Http::fake([$host.'/*' => $response]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function provider(AiProviderEnum $provider, string $model, array $attributes = []): AiProvider
    {
        return AiProvider::factory()->provider($provider, $model)->create($attributes);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function collect(AiProvider $provider, ?AiReasoningLevelEnum $level = null): array
    {
        $events = [];

        $provider->connector()->stream($provider, 'Sistema', 'Pregunta', $level, function (string $type, string $text) use (&$events): void {
            $events[] = [$type, $text];
        });

        return $events;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $events
     * @return list<string>
     */
    private function of(array $events, string $type): array
    {
        return array_values(array_map(fn (array $event): string => $event[1], array_filter($events, fn (array $event): bool => $event[0] === $type)));
    }

    /**
     * @param  list<array{0: string, 1: string}>  $events
     */
    private function text(array $events, string $type): string
    {
        return implode('', $this->of($events, $type));
    }

    /**
     * @param  string|list<array<string, mixed>>  $content
     * @return array<string, mixed>
     */
    private function mistral(string|array $content, ?string $finishReason = null): array
    {
        return ['choices' => [['index' => 0, 'delta' => ['content' => $content], 'finish_reason' => $finishReason]]];
    }

    /**
     * Un cuerpo SSE: cada evento en su `data:` (con su `event:` si `$named`),
     * y `[DONE]` al final si `$done`; detrás va un evento que no debe leerse.
     *
     * @param  list<array<string, mixed>>  $events
     */
    private function sseResponse(array $events, bool $named = false, bool $done = false): PromiseInterface
    {
        $body = '';

        foreach ($events as $event) {
            $body .= ($named && isset($event['type']) ? 'event: '.$event['type']."\n" : '').'data: '.json_encode($event)."\n\n";
        }

        if ($done) {
            $body .= "data: [DONE]\n\n".'data: '.json_encode($this->mistral('DESPUÉS DE DONE'))."\n\n";
        }

        return Http::response($body, 200, ['Content-Type' => 'text/event-stream']);
    }
}
