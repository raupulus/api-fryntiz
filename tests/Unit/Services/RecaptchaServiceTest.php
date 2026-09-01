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
    public function sin_clave_configurada_no_hay_captcha_y_se_deja_pasar(): void
    {
        config()->set('google.recaptcha.secret_key', null);

        $resultado = $this->service->verify('lo-que-sea');

        $this->assertTrue($resultado->valid);
        $this->assertFalse($resultado->configured);
        // Sin puntuación: «no configurado» no es lo mismo que «puntuación
        // perfecta», y quien lo use tiene que poder distinguirlo.
        $this->assertNull($resultado->score);
    }

    #[Test]
    public function con_clave_pero_sin_token_el_envio_no_es_valido(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');

        $resultado = $this->service->verify(null);

        $this->assertFalse($resultado->valid);
        $this->assertTrue($resultado->configured);
    }

    #[Test]
    public function una_verificacion_correcta_conserva_la_puntuacion(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.9]),
        ]);

        $resultado = $this->service->verify('token');

        $this->assertTrue($resultado->valid);
        $this->assertSame(0.9, $resultado->score);
    }

    #[Test]
    public function una_puntuacion_baja_se_marca_como_sospechosa(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');
        config()->set('contact.captcha.threshold', 0.5);

        Http::fake([
            'www.google.com/*' => Http::response(['success' => true, 'score' => 0.1]),
        ]);

        $this->assertTrue($this->service->verify('token')->isSuspicious());
    }

    #[Test]
    public function google_diciendo_que_no_es_un_envio_invalido(): void
    {
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake([
            'www.google.com/*' => Http::response(['success' => false]),
        ]);

        $this->assertFalse($this->service->verify('token')->valid);
    }

    #[Test]
    public function si_google_no_responde_el_envio_se_acepta(): void
    {
        // FALLO EN ABIERTO DELIBERADO (SEC-05). No se cierra el acceso al sitio
        // porque un tercero se caiga. Si algún día resulta ser un problema de
        // verdad, la salida es cambiar de proveedor, no dejar a la gente fuera
        // mientras tanto.
        config()->set('google.recaptcha.secret_key', 'una-clave');

        Http::fake(function () {
            throw new \RuntimeException('Sin conexión con Google');
        });

        $resultado = $this->service->verify('token');

        $this->assertTrue($resultado->valid);
        $this->assertTrue($resultado->configured);
        // Sin puntuación, así que no puede darse por sospechoso tampoco.
        $this->assertNull($resultado->score);
        $this->assertFalse($resultado->isSuspicious());
    }

    #[Test]
    public function si_google_responde_con_un_error_http_el_envio_se_acepta(): void
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
    public function is_configured_refleja_si_hay_clave(): void
    {
        config()->set('google.recaptcha.secret_key', null);
        $this->assertFalse($this->service->isConfigured());

        config()->set('google.recaptcha.secret_key', 'una-clave');
        $this->assertTrue($this->service->isConfigured());
    }
}
