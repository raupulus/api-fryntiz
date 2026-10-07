<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\Email;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class ContactTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    #[Test]
    public function contact_validates_required_fields(): void
    {
        config(['google.recaptcha.secret_key' => 'test-secret']);
        $response = $this->postJson($this->apiUrl('contact-messages'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['name', 'email', 'subject', 'message', 'g-recaptcha-response']);
    }

    #[Test]
    public function contact_omits_recaptcha_validation_when_secret_key_is_empty(): void
    {
        config(['google.recaptcha.secret_key' => null]);
        $response = $this->postJson($this->apiUrl('contact-messages'), []);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
        $response->assertJsonMissingValidationErrors(['g-recaptcha-response']);
    }

    #[Test]
    public function contact_validates_email_format(): void
    {
        $response = $this->postJson($this->apiUrl('contact-messages'), [
            'name' => 'Test', 'email' => 'invalid',
            'subject' => 'Test', 'message' => 'Mensaje largo suficiente para pasar validación mínima',
            'g-recaptcha-response' => 'fake',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function contact_validates_message_min_length(): void
    {
        $response = $this->postJson($this->apiUrl('contact-messages'), [
            'name' => 'Test', 'email' => 'test@test.com',
            'subject' => 'Test', 'message' => 'Corto',
            'g-recaptcha-response' => 'fake',
        ]);
        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['message']);
    }

    #[Test]
    public function contact_sends_successfully_with_valid_recaptcha(): void
    {
        $this->enableCaptcha(recaptcha: true);
        Http::fake(['www.google.com/*' => Http::response(['success' => true, 'score' => 0.9])]);

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'g-recaptcha-response' => 'valid-token',
        ]));

        // El alta de un mensaje crea un recurso: 201, no 200.
        $this->assertSuccessResponse($response, 201);
        $response->assertJson(['message' => 'Mensaje recibido correctamente']);
        $this->assertSame(1, Email::query()->count());
        $email = Email::query()->first();
        $this->assertNotNull($email);
        $this->assertSame('0.90', (string) $email->captcha_score);
        $this->assertSame('recaptcha', $email->attributes['captcha_provider'] ?? null);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'recaptcha-secret'
            && $request['response'] === 'valid-token');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'cloudflare'));
    }

    #[Test]
    public function contact_accepts_the_recaptcha_token_in_its_alternative_field(): void
    {
        $this->enableCaptcha(recaptcha: true);
        Http::fake(['www.google.com/*' => Http::response(['success' => true, 'score' => 0.9])]);

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'recaptcha_token' => 'valid-token',
        ]));

        $this->assertSuccessResponse($response, 201);
        Http::assertSent(fn (Request $request) => $request['response'] === 'valid-token');
    }

    #[Test]
    public function contact_fails_with_invalid_recaptcha(): void
    {
        $this->enableCaptcha(recaptcha: true);
        Http::fake(['www.google.com/*' => Http::response(['success' => false])]);

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'g-recaptcha-response' => 'invalid-token',
        ]));

        $this->assertErrorResponse($response, 422);
        $response->assertJson(['message' => 'Verificacion de seguridad fallida']);
        // Se corta antes de guardar: no interesa conservar la basura.
        $this->assertSame(0, Email::query()->count());
    }

    #[Test]
    public function contact_sends_successfully_with_valid_turnstile(): void
    {
        $this->enableCaptcha(turnstile: true);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'cf-turnstile-response' => 'valid-token',
        ]));

        $this->assertSuccessResponse($response, 201);
        $response->assertJson(['message' => 'Mensaje recibido correctamente']);
        $this->assertSame(1, Email::query()->count());
        $email = Email::query()->first();
        $this->assertNotNull($email);
        // Turnstile no da puntuación.
        $this->assertNull($email->captcha_score);
        $this->assertSame('turnstile', $email->attributes['captcha_provider'] ?? null);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'turnstile-secret'
            && $request['response'] === 'valid-token'
            && isset($request['remoteip']));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'google.com'));
    }

    #[Test]
    public function contact_accepts_the_turnstile_token_in_its_alternative_field(): void
    {
        $this->enableCaptcha(turnstile: true);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'turnstile_token' => 'valid-token',
        ]));

        $this->assertSuccessResponse($response, 201);
        Http::assertSent(fn (Request $request) => $request['response'] === 'valid-token');
    }

    #[Test]
    public function contact_fails_with_invalid_turnstile(): void
    {
        $this->enableCaptcha(turnstile: true);
        Http::fake(['challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ])]);

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'cf-turnstile-response' => 'invalid-token',
        ]));

        $this->assertErrorResponse($response, 422);
        $response->assertJson(['message' => 'Verificacion de seguridad fallida']);
        $this->assertSame(0, Email::query()->count());
    }

    #[Test]
    public function both_providers_can_be_configured_and_each_token_goes_to_its_own(): void
    {
        $this->enableCaptcha(recaptcha: true, turnstile: true);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'cf-turnstile-response' => 'turnstile-token',
        ]))->assertCreated();
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'cloudflare'));

        $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'subject' => 'Otro asunto distinto',
            'email' => 'otro@example.com',
            'g-recaptcha-response' => 'recaptcha-token',
        ]))->assertCreated();
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'google.com'));
    }

    #[Test]
    public function contact_requires_a_token_when_only_turnstile_is_configured(): void
    {
        $this->enableCaptcha(turnstile: true);
        Http::fake();

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload());

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['g-recaptcha-response']);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_token_for_a_provider_that_is_not_configured_does_not_bypass_the_check(): void
    {
        // Sólo reCAPTCHA configurado: mandar un token de Turnstile cualquiera
        // no puede ser un atajo para saltarse la comprobación.
        $this->enableCaptcha(recaptcha: true);
        Http::fake();

        $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
            'cf-turnstile-response' => 'whatever',
        ]));

        $this->assertErrorResponse($response, 422);
        $this->assertSame(0, Email::query()->count());
        Http::assertNothingSent();
    }

    #[Test]
    public function without_any_key_configured_the_check_is_skipped_for_either_provider(): void
    {
        $this->enableCaptcha();
        Http::fake();

        foreach (['cf-turnstile-response', 'g-recaptcha-response'] as $i => $field) {
            $response = $this->postJson($this->apiUrl('contact-messages'), $this->payload([
                $field => 'whatever',
                'email' => "dev{$i}@example.com",
                'subject' => "Asunto de prueba número {$i}",
            ]));

            $this->assertSuccessResponse($response, 201);
        }

        Http::assertNothingSent();
    }

    /**
     * Configura qué proveedores de captcha tienen clave en este test.
     */
    private function enableCaptcha(bool $recaptcha = false, bool $turnstile = false): void
    {
        config([
            'google.recaptcha.secret_key' => $recaptcha ? 'recaptcha-secret' : null,
            'services.turnstile.secret_key' => $turnstile ? 'turnstile-secret' : null,
        ]);

        // El job de envío no es lo que se prueba aquí.
        Queue::fake();
    }

    /**
     * Cuerpo válido de un mensaje, con los campos sobrescritos que se pidan.
     *
     * @param  array<string,mixed>  $overrides
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Este es un mensaje de prueba con longitud suficiente.',
        ];
    }
}
