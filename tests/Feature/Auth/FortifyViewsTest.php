<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fortify no registra ninguna ruta en esta aplicación.
 *
 * El login y el logout reales son los de Filament; de Fortify sólo se usan las
 * acciones y el trait `TwoFactorAuthenticatable`. `Fortify::ignoreRoutes()` en
 * `FortifyServiceProvider::register()` las quita todas.
 *
 * Dos motivos: `GET /login` respondía **500** en producción el 2026-09-06
 * (`Target [Laravel\Fortify\Contracts\LoginViewResponse] is not instantiable`),
 * y `POST /login` autenticaba saltándose el reCAPTCHA del panel.
 *
 * No se redirigen al panel: se quitan. Una redirección le confirma a quien
 * rastrea dónde está el login de verdad; un 404 no le dice nada.
 */
class FortifyViewsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function rutasQueNoExistenProvider(): array
    {
        return [
            'login' => ['/login'],
            'desafío de doble factor' => ['/two-factor-challenge'],
            'confirmar contraseña' => ['/user/confirm-password'],
        ];
    }

    #[Test]
    #[DataProvider('rutasQueNoExistenProvider')]
    public function las_vistas_de_fortify_responden_404(string $ruta): void
    {
        $respuesta = $this->get($ruta);

        $respuesta->assertNotFound();
        // La 404 de la propia web, no una pantalla de Laravel ni una redirección.
        $respuesta->assertSee('Esta página no existe', false);
    }

    #[Test]
    public function el_login_de_filament_sigue_en_pie(): void
    {
        $this->get('/panel/login')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    /**
     * `POST /login` autenticaba por Fortify sin pasar por el formulario de
     * Filament, y por tanto sin su reCAPTCHA.
     */
    #[Test]
    public function fortify_no_registra_ninguna_ruta(): void
    {
        foreach ([
            'login', 'login.store', 'logout',
            'two-factor.login', 'two-factor.login.store',
            'password.confirm', 'password.confirm.store',
            'two-factor.enable', 'two-factor.qr-code',
        ] as $nombre) {
            $this->assertFalse(
                Route::has($nombre),
                "La ruta «{$nombre}» de Fortify sigue registrada."
            );
        }

        // El logout de verdad es el del panel.
        $this->assertTrue(Route::has('filament.admin.auth.logout'));
        $this->assertTrue(Route::has('filament.tenant.auth.logout'));
    }
}
