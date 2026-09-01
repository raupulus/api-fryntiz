<?php

declare(strict_types=1);

namespace Tests\Feature\Docs;

use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La documentación de la API la sirve la propia aplicación
 * (`routes/web.php`), no el ServiceProvider de Scribe.
 *
 * Scribe está en `require-dev`, así que en el servidor —donde se instala con
 * `composer install --no-dev`— el paquete no existe: la ruta que registraba él
 * desaparecía y `/docs` respondía 404. Lo que se despliega es el resultado ya
 * generado y commiteado (la vista, los assets y los ficheros de `storage/app/scribe`).
 *
 * Estas pruebas fijan las dos cosas que importan: que la documentación se sirve
 * y que sigue siendo privada.
 */
class ScribeDocsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La factory de usuarios apunta a `role_id`, que es FK de `user_roles`.
        (new RolesTableSeeder)->run();
    }

    #[Test]
    public function the_documentation_is_not_public(): void
    {
        $this->get('/docs')->assertRedirect();
        $this->get('/docs.openapi')->assertRedirect();
        $this->get('/docs.postman')->assertRedirect();
    }

    #[Test]
    public function an_authenticated_user_sees_the_documentation_with_the_public_url(): void
    {
        // Las dos comprobaciones comparten petición a propósito: la página pasa
        // de las 9.000 líneas y renderizarla dos veces engorda la suite sin
        // aportar nada.
        $response = $this->actingAs(User::factory()->create())->get('/docs');

        $response->assertOk();
        $response->assertSee('Api Raupulus', false);

        // El fallo que motiva esta prueba: la página se genera en local, y con
        // `base_url` saliendo de `APP_URL` acababa publicada anunciando
        // `http://localhost:8000` como URL de la API y en el botón «Try it out».
        //
        // Se comprueba la URL de la API, no la página entera: las etiquetas de
        // CSS y JS salen de `asset()`, o sea de `APP_URL`, y esa sí es la del
        // entorno donde se sirve la documentación.
        $baseUrl = (string) config('scribe.base_url');

        $this->assertStringStartsWith('https://', $baseUrl);
        $response->assertSee('var tryItOutBaseUrl = "'.$baseUrl.'"', false);
    }

    #[Test]
    public function the_openapi_spec_and_the_postman_collection_are_served(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/docs.openapi')->assertOk();
        $this->actingAs($user)->get('/docs.postman')->assertOk();
    }
}
