<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las rutas de vista de Fortify no pueden responder 500.
 *
 * `config/fortify.php` tiene `views => true`, así que Fortify registra
 * `GET /login`, `GET /two-factor-challenge` y `GET /user/confirm-password`.
 * Aquí no hay vista propia para ninguna —el login real pasa por Filament—, y
 * sin registrar nada revientan con
 * `Target [Laravel\Fortify\Contracts\LoginViewResponse] is not instantiable`.
 *
 * Pasó en producción el 2026-09-06 con alguien entrando a `/login` a mano.
 */
class FortifyViewsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function la_ruta_de_login_de_fortify_lleva_al_login_del_panel(): void
    {
        $this->get('/login')->assertRedirect(route('filament.tenant.auth.login'));
    }

    #[Test]
    public function la_ruta_de_confirmar_contrasena_no_revienta(): void
    {
        $respuesta = $this->get('/user/confirm-password');

        $this->assertNotSame(500, $respuesta->getStatusCode());
    }

    #[Test]
    public function la_ruta_del_desafio_de_doble_factor_no_revienta(): void
    {
        $respuesta = $this->get('/two-factor-challenge');

        $this->assertNotSame(500, $respuesta->getStatusCode());
    }
}
