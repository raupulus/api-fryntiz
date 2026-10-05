<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TurnstileService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verificación de Cloudflare Turnstile.
 *
 * Mismo criterio que `RecaptchaServiceTest`: sin clave no se comprueba nada y
 * si Cloudflare no responde se deja pasar (D10). La diferencia es que un 4xx es
 * Cloudflare rechazando nuestra petición, y eso no se deja pasar.
 */
class TurnstileServiceTest extends TestCase
{
    private TurnstileService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.turnstile.secret_key', 'una-clave');
        $this->service = app(TurnstileService::class);
    }

    #[Test]
    public function without_a_configured_key_there_is_no_captcha_and_it_passes(): void
    {
        config()->set('services.turnstile.secret_key', null);
        Http::fake();

        $result = $this->service->verify('lo-que-sea');

        $this->assertTrue($result->valid);
        $this->assertFalse($result->configured);
        $this->assertNull($result->score);
        $this->assertFalse($this->service->isConfigured());
        Http::assertNothingSent();
    }

    #[Test]
    public function with_a_key_but_no_token_the_submission_is_invalid(): void
    {
        Http::fake();

        $result = $this->service->verify(null);

        $this->assertFalse($result->valid);
        $this->assertTrue($result->configured);
        Http::assertNothingSent();
    }

    #[Test]
    public function a_successful_verification_is_valid_and_sends_secret_token_and_ip(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $result = $this->service->verify('token', '203.0.113.7');

        $this->assertTrue($result->valid);
        $this->assertTrue($result->configured);
        $this->assertNull($result->score);
        Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request->method() === 'POST'
            && $request['secret'] === 'una-clave'
            && $request['response'] === 'token'
            && $request['remoteip'] === '203.0.113.7');
    }

    #[Test]
    public function the_ip_is_optional(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->service->verify('token');

        Http::assertSent(fn ($request) => ! isset($request['remoteip']));
    }

    #[Test]
    public function cloudflare_returning_failure_makes_the_submission_invalid(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ])]);

        $this->assertFalse($this->service->verify('token')->valid);
    }

    #[Test]
    public function a_4xx_from_cloudflare_is_a_rejection_not_a_fail_open(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['bad-request']], 400)]);

        $this->assertFalse($this->service->verify('token')->valid);
    }

    #[Test]
    public function a_wrong_secret_key_is_logged_because_it_rejects_every_visitor(): void
    {
        Log::shouldReceive('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'TURNSTILE_SECRET_KEY'));
        Http::fake(['challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-secret'],
        ])]);

        $this->assertFalse($this->service->verify('token')->valid);
    }

    #[Test]
    public function if_cloudflare_does_not_respond_the_submission_is_accepted(): void
    {
        // Fallo en abierto deliberado, el mismo criterio que reCAPTCHA (D10).
        Http::fake(function () {
            throw new \RuntimeException('Sin conexión con Cloudflare');
        });

        $result = $this->service->verify('token');

        $this->assertTrue($result->valid);
        $this->assertTrue($result->configured);
        $this->assertNull($result->score);
    }

    #[Test]
    public function if_cloudflare_responds_with_a_server_error_the_submission_is_accepted(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response('', 500)]);

        $this->assertTrue($this->service->verify('token')->valid);
    }
}
