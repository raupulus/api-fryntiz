<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las rutas de vista de Fortify no existen aquí.
 *
 * Esta aplicación no tiene ninguna vista de Fortify: el login real pasa por
 * Filament (`/admin/login`, `/panel/login`) y no hay registro público. Con
 * `views => true`, Fortify las registraba igual y respondían **500**
 * (`Target [Laravel\Fortify\Contracts\LoginViewResponse] is not instantiable`),
 * como pasó en producción el 2026-09-06 con alguien pidiendo `/login` a mano.
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
}
