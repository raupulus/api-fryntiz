<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `project:check-config` avisa de captcha sin claves en producción. Con
 * Turnstile y reCAPTCHA como alternativas, basta con que haya una de las dos.
 */
class ProjectCheckConfigCaptchaTest extends TestCase
{
    private const TITLE = 'RECAPTCHA_SECRET_KEY y TURNSTILE_SECRET_KEY vacías en producción';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn () => 'production');
    }

    #[Test]
    public function it_fails_when_neither_provider_has_a_key(): void
    {
        config(['google.recaptcha.secret_key' => null, 'services.turnstile.secret_key' => null]);

        Artisan::call('project:check-config');

        $this->assertStringContainsString(self::TITLE, Artisan::output());
    }

    #[Test]
    public function it_does_not_complain_with_only_recaptcha(): void
    {
        config(['google.recaptcha.secret_key' => 'una-clave', 'services.turnstile.secret_key' => null]);

        Artisan::call('project:check-config');

        $this->assertStringNotContainsString(self::TITLE, Artisan::output());
    }

    #[Test]
    public function it_does_not_complain_with_only_turnstile(): void
    {
        config(['google.recaptcha.secret_key' => null, 'services.turnstile.secret_key' => 'una-clave']);

        Artisan::call('project:check-config');

        $this->assertStringNotContainsString(self::TITLE, Artisan::output());
    }
}
