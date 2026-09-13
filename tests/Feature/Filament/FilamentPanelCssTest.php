<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Support\FilamentPanelCss;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El CSS propio de los paneles: `resources/css/filament/admin/panel.css`.
 *
 * Lo que se sujeta es que llegue de verdad al navegador. Ya se dio dos veces por
 * buena una maquetación que en el panel no cargaba, con la suite en verde.
 */
class FilamentPanelCssTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Se prueba el camino de producción: la hoja compilada del manifest.
     *
     * Con `npm run dev` en marcha existe `public/hot` y Vite enlaza el servidor
     * de desarrollo en vez de `build/assets/…`; estas pruebas fallarían en el
     * equipo de quien esté trabajando y pasarían en el servidor. Apuntar el
     * fichero `hot` a una ruta que no existe las deja iguales en los dos sitios.
     */
    protected function setUp(): void
    {
        parent::setUp();

        app(Vite::class)->useHotFile(storage_path('framework/testing/sin-vite-dev'));
    }

    /**
     * Si se añade la entrada y no se compila, el panel carga sin estilos
     * propios —la etiqueta falla en silencio para no tumbarlo—. Esto es lo que
     * avisa antes de que pase en producción.
     */
    #[Test]
    public function la_entrada_esta_compilada_en_el_manifest(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);

        $this->assertIsArray($manifest);
        $this->assertArrayHasKey(
            FilamentPanelCss::ENTRADA,
            $manifest,
            'panel.css no está en public/build/manifest.json: falta `npm run build`.',
        );
    }

    #[Test]
    public function la_entrada_esta_en_vite_config(): void
    {
        $this->assertStringContainsString(
            FilamentPanelCss::ENTRADA,
            (string) file_get_contents(base_path('vite.config.js')),
        );
    }

    #[Test]
    public function la_etiqueta_es_un_link_a_la_hoja_compilada(): void
    {
        $this->assertMatchesRegularExpression(
            '/<link[^>]+rel="stylesheet"[^>]+build\/assets\/panel-[^"]+\.css/',
            FilamentPanelCss::etiqueta(),
        );
    }

    #[Test]
    public function el_panel_de_administracion_la_carga(): void
    {
        (new RolesTableSeeder)->run();
        $this->actingAs(User::factory()->create(['role_id' => 1, 'is_active' => true]));

        $this->get('/admin')
            ->assertSuccessful()
            ->assertSee('build/assets/panel-', escape: false);
    }

    /**
     * El formulario de acceso también es del panel y también tiene que llevarla.
     */
    #[Test]
    public function el_panel_de_usuario_la_carga(): void
    {
        $this->get('/panel/login')
            ->assertSuccessful()
            ->assertSee('build/assets/panel-', escape: false);
    }

    /**
     * No es un tema de Tailwind: si alguien le mete `@import "tailwindcss"`,
     * recompilaría y pisaría los estilos de Filament en todo el panel.
     */
    #[Test]
    public function panel_css_no_es_tailwind(): void
    {
        // Sin comentarios: la cabecera del fichero explica, precisamente, que
        // no lleva ninguna de las dos cosas.
        $css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(base_path(FilamentPanelCss::ENTRADA)));

        $this->assertStringNotContainsString('@import "tailwindcss"', $css);
        $this->assertStringNotContainsString('@apply', $css);
    }
}
