<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Models\Content\Content;
use App\Models\User;
use Database\Seeders\ContentAvailablePageRawSeeder;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Editor.js del panel, empaquetado por Vite (`resources/js/filament/editorjs.js`).
 *
 * Antes eran ficheros sueltos en `public/vendor/editorjs`, de versiones
 * mezcladas y sin control de qué había. Lo que se sujeta aquí es que la
 * entrada esté compilada, que la carguen las páginas con editor y que las
 * versiones sigan fijadas.
 */
class EditorJsAssetsTest extends TestCase
{
    use RefreshDatabase;

    private const ENTRY = 'resources/js/filament/editorjs.js';

    protected function setUp(): void
    {
        parent::setUp();

        // Como en FilamentPanelCssTest: el camino de producción, no el de `npm run dev`.
        app(Vite::class)->useHotFile(storage_path('framework/testing/sin-vite-dev'));
    }

    #[Test]
    public function the_entry_is_compiled_in_the_manifest(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);

        $this->assertArrayHasKey(self::ENTRY, $manifest, 'El editor no está en public/build/manifest.json: falta `pnpm build`.');
        $this->assertStringContainsString(self::ENTRY, (string) file_get_contents(base_path('vite.config.js')));
    }

    #[Test]
    public function the_pages_screen_loads_the_bundle_and_the_endpoints(): void
    {
        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();
        (new ContentAvailablePageRawSeeder)->run();

        $this->actingAs(User::factory()->create(['role_id' => 1, 'is_active' => true]));
        $content = Content::factory()->create();

        // El editor está en la pantalla de páginas (F8), y sólo ahí se carga.
        $this->get(ContentResource::getUrl('edit', ['record' => $content], panel: 'admin'))
            ->assertSuccessful()
            ->assertDontSee('build/assets/editorjs-', escape: false);

        $this->get(ContentResource::getUrl('pages', ['record' => $content, 'page' => 'new'], panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('build/assets/editorjs-', escape: false)
            ->assertSee('build/assets/content-pages-', escape: false)
            // El campo pide el token vigente antes de cada subida (D3).
            ->assertSee('csrfUrl', escape: false)
            // Las rutas de subida ya no van en una variable global: cada campo
            // lleva las de su contenido (`EditorJsField::getEndpoints()`).
            ->assertDontSee('window.editorJsEndpoints', escape: false)
            ->assertDontSee('vendor/editorjs', escape: false);
    }

    #[Test]
    public function the_rest_of_the_panel_does_not_load_it(): void
    {
        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create(['role_id' => 1, 'is_active' => true]));

        $this->get('/admin')
            ->assertSuccessful()
            ->assertDontSee('build/assets/editorjs-', escape: false);
    }

    /**
     * Versiones exactas: una actualización del editor cambia el formato de
     * los datos (pasó con las listas en la 2.x) y tiene que ser a propósito,
     * con `ServedHtmlRegressionTest` pasado con las páginas regrabadas.
     */
    #[Test]
    public function the_editor_packages_have_pinned_versions(): void
    {
        $dependencies = json_decode((string) file_get_contents(base_path('package.json')), true)['dependencies'];

        $editor = array_filter(
            $dependencies,
            fn (string $package): bool => str_starts_with($package, '@editorjs/') || in_array($package, ['@calumk/editorjs-codecup', 'editorjs-alert', 'prismjs'], true),
            ARRAY_FILTER_USE_KEY,
        );

        $this->assertArrayHasKey('@editorjs/editorjs', $editor);

        foreach ($editor as $package => $version) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version, "{$package} no tiene la versión fijada ({$version}).");
        }
    }

    /**
     * Toda `Cmd/Ctrl+Mayús+letra` la usa ya el navegador o sus herramientas
     * de desarrollo, y la de «aviso» que venía de `main` (+W) cerraba la
     * ventana sin que la página pudiera impedirlo (DUDA-7, D43). Las
     * herramientas no llevan atajo propio: los bloques van con «/» y resaltar
     * y código en línea, con la barra de la selección.
     */
    #[Test]
    public function the_editor_tools_have_no_keyboard_shortcuts_of_their_own(): void
    {
        $source = (string) file_get_contents(base_path(self::ENTRY));

        $this->assertDoesNotMatchRegularExpression('/\bshortcut\s*:/', $source);
    }

    #[Test]
    public function the_old_loose_files_are_gone(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('vendor/editorjs'));
    }
}
