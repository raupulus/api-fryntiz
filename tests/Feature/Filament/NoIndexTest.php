<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Http\Middleware\NoIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ni el panel ni su formulario de acceso deben acabar en un buscador.
 *
 * `public/robots.txt` ya pedía no rastrear `/admin`, `/panel` y `/login`, pero
 * eso no impide indexar: un buscador que llegue por un enlace externo puede
 * listar la URL sin abrir la página, y el propio `Disallow` le impide leer un
 * `<meta name="robots">` que estuviera dentro. Hace falta la cabecera.
 *
 * El riesgo de este cambio es el contrario: que la cabecera se escape a las
 * páginas públicas y se desindexe la web entera. La segunda mitad de este test
 * es la que importa de verdad.
 */
class NoIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{string}>
     */
    public static function panelRoutes(): array
    {
        return [
            'login del admin' => ['/admin/login'],
            'login del tenant' => ['/panel/login'],
            'raíz del admin' => ['/admin'],
            'raíz del tenant' => ['/panel'],
        ];
    }

    #[Test]
    #[DataProvider('panelRoutes')]
    public function panel_routes_ask_not_to_be_indexed(string $route): void
    {
        // Sin autenticar: una raíz de panel redirige a su login, y la cabecera
        // tiene que viajar también en la redirección.
        $this->get($route)->assertHeader('X-Robots-Tag', NoIndex::VALOR);
    }

    #[Test]
    public function the_login_page_also_carries_the_meta_tag(): void
    {
        // Respaldo por si un proxy delante se comiera la cabecera.
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="'.NoIndex::VALOR.'">', escape: false);
    }

    /**
     * @return list<array{string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'portada' => ['/'],
            'energía' => ['/hardware/energy'],
            'smartplant' => ['/smartplant'],
        ];
    }

    #[Test]
    #[DataProvider('publicRoutes')]
    public function public_pages_keep_being_indexed(string $route): void
    {
        $response = $this->get($route);

        $this->assertFalse(
            $response->headers->has('X-Robots-Tag'),
            "La ruta pública {$route} está pidiendo no ser indexada. El "
            .'middleware NoIndex se ha escapado del panel.'
        );
    }
}
