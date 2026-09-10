<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\RecaptchaService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verificación de reCAPTCHA, y sobre todo su comportamiento cuando Google no
 * contesta.
 *
 * Los dos tests de fallo en abierto están aquí a propósito: ese
 * comportamiento es una DECISIÓN (SEC-05, ver docs/info/decisiones-tecnicas.md)
 * y no un descuido. Sin un test que lo fije, la próxima auditoría lo marca
 * como bug, alguien lo "arregla" cerrando el paso, y el sitio deja de aceptar
 * mensajes el día que Google tenga una mala tarde.
 */
class RecaptchaServiceTest extends TestCase
{
    private RecaptchaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RecaptchaService::class);
    }

    #[Test]
    public function without_a_configured_key_there_is_no_captcha_and_it_passes(): void
    {
        config()->set('google.recaptcha.secret_key', null);

        $result = $this->service->verify('lo-que-sea');

        $this->assertTrue($result->valid);
        $this->assertFalse($result->configured);
        // Sin puntuación: «no configurado» no es lo mismo que «puntuación
        // perfecta», y quien lo use tiene que poder distinguirlo.
        $this->assertNull($result->score);
    }

    #[Test]
    public function with_a_key_but_no_token_the_submission_is_invalid(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');

        $result = $this->service->verify(null);

        $this->assertFalse($result->valid);
        $this->assertTrue($result->configured);
    }

    #[Test]
    public function a_successful_verification_keeps_the_score(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $result = $this->service->verify('token');

        $this->assertTrue($result->valid);
        $this->assertSame(0.9, $result->score);
    }

    #[Test]
    public function a_low_score_is_marked_as_suspicious(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');
        config()->set('contact.captcha.threshold', 0.5);

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.1]),
        ]);

        $this->assertTrue($this->service->verify('token')->isSuspicious());
    }

    #[Test]
    public function google_returning_failure_makes_the_submission_invalid(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake([
            'www.google.com/*' => Http::response(['success' => false]),
        ]);

        $this->assertFalse($this->service->verify('token')->valid);
    }

    #[Test]
    public function if_google_does_not_respond_the_submission_is_accepted(): void
    {
        // FALLO EN ABIERTO DELIBERADO (SEC-05). No se cierra el acceso al sitio
        // porque un tercero se caiga. Si algún día resulta ser un problema de
        // verdad, la salida es cambiar de proveedor, no dejar a la gente fuera
        // mientras tanto.
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake(function () {
            throw new \RuntimeException('Sin conexión con Google');
        });

        $result = $this->service->verify('token');

        $this->assertTrue($result->valid);
        $this->assertTrue($result->configured);
        // Sin puntuación, así que no puede darse por sospechoso tampoco.
        $this->assertNull($result->score);
        $this->assertFalse($result->isSuspicious());
    }

    #[Test]
    public function if_google_responds_with_an_http_error_the_submission_is_accepted(): void
    {
        // Misma decisión que el test de arriba, para el otro camino: un 500 de
        // Google tampoco convierte a nadie en bot.
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake([
            'www.google.com/*' => Http::response('', 500),
        ]);

        $this->assertTrue($this->service->verify('token')->valid);
    }

    #[Test]
    public function is_configured_reflects_whether_a_key_is_set(): void
    {
        config()->set('google.recaptcha.secret_key', null);
        $this->assertFalse($this->service->isConfigured());

        config()->set('google.recaptcha.secret_key', 'una-clave');
        $this->assertTrue($this->service->isConfigured());
    }

    #[Test]
    public function a_low_score_is_rejected_even_when_success_is_true(): void
    {
        // El fallo que motivó AR-S04: `success` es cierto para cualquier token
        // bien formado y sin caducar, también el que se saca un bot. Lo que
        // separa persona de bot en v3 es la puntuación, y no se miraba.
        config(['google.recaptcha.secret_key' => 'clave-de-prueba']);
        config(['google.recaptcha.min_score' => 0.5]);

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.1]),
        ]);

        $result = (new RecaptchaService)->verify('token-de-un-bot');

        $this->assertTrue($result->configured);
        $this->assertSame(0.1, $result->score);
        $this->assertFalse($result->valid, 'Una puntuación de 0.1 no puede darse por válida.');
    }

    #[Test]
    public function a_high_score_is_accepted(): void
    {
        config(['google.recaptcha.secret_key' => 'clave-de-prueba']);
        config(['google.recaptcha.min_score' => 0.5]);

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $this->assertTrue((new RecaptchaService)->verify('token-de-una-persona')->valid);
    }

    #[Test]
    public function login_uses_its_own_more_permissive_threshold(): void
    {
        // 0.4 pasa en el login (umbral 0.3) y no pasa en un formulario público
        // (umbral 0.5). Lo peor que puede pasar cortando en el login es dejar
        // fuera al dueño del panel.
        config(['google.recaptcha.secret_key' => 'clave-de-prueba']);
        config(['google.recaptcha.min_score' => 0.5]);
        config(['google.recaptcha.min_score_login' => 0.3]);

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.4]),
        ]);

        $service = new RecaptchaService;

        $this->assertFalse($service->verify('token')->valid, 'En un formulario público 0.4 debe cortarse.');
        $this->assertTrue($service->verify('token', null, 0.3)->valid, 'En el login 0.4 debe pasar.');
    }

    #[Test]
    public function without_a_score_it_passes(): void
    {
        // No debería ocurrir en v3, pero si Google responde sin `score` no se
        // puede afirmar que sea un bot: mismo criterio de fallo en abierto que
        // D10.
        config(['google.recaptcha.secret_key' => 'clave-de-prueba']);

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true]),
        ]);

        $result = (new RecaptchaService)->verify('token');

        $this->assertTrue($result->valid);
        $this->assertNull($result->score);
    }
}
