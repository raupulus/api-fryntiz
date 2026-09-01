<?php

declare(strict_types=1);

namespace Tests\Feature\Docs;

use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
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

    /**
     * La documentación se genera a mano con `php artisan scribe:generate` y se
     * commitea; el despliegue no la regenera, porque Scribe está en
     * `require-dev` y en el servidor no existe.
     *
     * Consecuencia: es facilísimo tocar una ruta y subir la documentación
     * anterior, que es peor que no tener ninguna — quien la lee se fía. Este
     * test cruza las rutas reales de la API con las que hay documentadas y
     * falla nombrando las que faltan o las que sobran.
     */
    #[Test]
    public function the_generated_documentation_covers_every_api_route(): void
    {
        $documentadas = $this->documentedRoutes();

        $this->assertNotEmpty(
            $documentadas,
            'No hay documentación generada en .scribe/endpoints/. Ejecuta: php artisan scribe:generate'
        );

        $reales = $this->apiRoutes();

        $sinDocumentar = array_values(array_diff($reales, $documentadas));
        $fantasma = array_values(array_diff($documentadas, $reales));

        $this->assertSame([], $sinDocumentar, sprintf(
            "%d ruta(s) de la API sin documentar. Ejecuta `php artisan scribe:generate`:\n  - %s\n",
            count($sinDocumentar),
            implode("\n  - ", $sinDocumentar)
        ));

        $this->assertSame([], $fantasma, sprintf(
            "%d ruta(s) documentadas que ya NO existen. Ejecuta `php artisan scribe:generate`:\n  - %s\n",
            count($fantasma),
            implode("\n  - ", $fantasma)
        ));
    }

    /**
     * Rutas de la API que Scribe debería recoger, en formato `MÉTODO uri`.
     *
     * @return list<string>
     */
    private function apiRoutes(): array
    {
        $rutas = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'api/v2')) {
                continue;
            }

            foreach ($route->methods() as $metodo) {
                if (in_array($metodo, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $rutas[] = $metodo.' '.self::normaliza($uri);
            }
        }

        sort($rutas);

        return array_values(array_unique($rutas));
    }

    /**
     * Rutas presentes en los ficheros que genera Scribe.
     *
     * Se leen los YAML de `.scribe/endpoints/` en vez de la vista Blade porque
     * son la fuente de la que sale todo lo demás (la página, el OpenAPI y la
     * colección de Postman).
     *
     * @return list<string>
     */
    private function documentedRoutes(): array
    {
        $rutas = [];

        foreach (glob(base_path('.scribe/endpoints/*.yaml')) ?: [] as $fichero) {
            $datos = Yaml::parseFile($fichero);

            foreach ($datos['endpoints'] ?? [] as $endpoint) {
                $uri = $endpoint['uri'] ?? null;

                if (! is_string($uri) || ! str_starts_with($uri, 'api/v2')) {
                    continue;
                }

                foreach ($endpoint['httpMethods'] ?? [] as $metodo) {
                    if (in_array($metodo, ['HEAD', 'OPTIONS'], true)) {
                        continue;
                    }

                    $rutas[] = $metodo.' '.self::normaliza($uri);
                }
            }
        }

        sort($rutas);

        return array_values(array_unique($rutas));
    }

    /**
     * Iguala el nombre de los parámetros de ruta.
     *
     * Laravel y Scribe no los llaman igual cuando hay binding de modelo: la
     * ruta declara `{platform:slug}`, el router la expone como `{platform}` y
     * Scribe la documenta como `{slug}`. Es la misma ruta, así que se compara
     * la forma y no el nombre.
     */
    private static function normaliza(string $uri): string
    {
        return preg_replace('/\{[^}]+\}/', '{param}', $uri) ?? $uri;
    }
}
