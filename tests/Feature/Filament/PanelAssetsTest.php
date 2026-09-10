<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function base_path;
use function file_get_contents;
use function glob;
use function is_file;
use function preg_match_all;
use function str_replace;

/**
 * Los assets del panel tienen que llegar al servidor.
 *
 * El buscador de vídeos de YouTube dejó de funcionar en producción con este
 * error en consola:
 *
 *     El recurso de "https://api.raupulus.dev/js/youtube_video_search.js" fue
 *     bloqueado debido a una discordancia del tipo MIME ("text/html")
 *
 * No era un problema de tipos MIME: el fichero vivía en `public/js/`, que
 * `.gitignore` excluye —era la carpeta de salida de Laravel Mix en la v1—, así
 * que nunca se subió. La petición caía en el enrutador, Laravel devolvía su
 * página de error en HTML y el navegador la rechazaba por `nosniff`.
 *
 * Un asset que se sirve con `asset('js/…')` o `asset('css/…')` no pasa por el
 * bundler y vuelve a caer en el mismo agujero. Esto lo impide.
 */
class PanelAssetsTest extends TestCase
{
    #[Test]
    public function no_view_loads_assets_from_ignored_directories(): void
    {
        $offenders = [];

        foreach ($this->views() as $view) {
            $content = (string) file_get_contents($view);

            // `public/js` y `public/css` están en `.gitignore`: lo que se
            // cargue desde ahí no existe en el servidor.
            preg_match_all(
                '/asset\(\s*[\'"](?:js|css)\//',
                $content,
                $matches
            );

            if ($matches[0] !== []) {
                $offenders[] = str_replace(base_path().'/', '', $view);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Estas vistas cargan assets desde public/js o public/css, que no se '
            .'versionan y no llegan al servidor. Muévelos a resources/ y '
            .'cárgalos con @vite().'
        );
    }

    #[Test]
    public function the_youtube_search_widget_loads_from_resources(): void
    {
        $this->assertFileExists(
            base_path('resources/js/youtube-video-search.js'),
            'El plugin del buscador de vídeos tiene que vivir en resources/ '
            .'para que lo compile Vite.'
        );

        $view = (string) file_get_contents(
            base_path('resources/views/filament/components/youtube-video-field.blade.php')
        );

        $this->assertStringContainsString(
            "@vite('resources/js/youtube-video-search.js')",
            $view
        );
    }

    /**
     * Todas las vistas Blade del proyecto, menos las de terceros.
     *
     * `resources/views/scribe` es documentación generada: no se edita a mano y
     * no tiene por qué seguir las reglas de aquí.
     *
     * @return list<string>
     */
    private function views(): array
    {
        // `glob()` no baja recursivamente, así que se recorre a mano.
        $pending = [base_path('resources/views')];
        $found = [];

        while ($pending !== []) {
            $directory = array_pop($pending);

            foreach ((array) glob($directory.'/*') as $path) {
                if (is_dir($path)) {
                    if (! str_ends_with($path, '/scribe')) {
                        $pending[] = $path;
                    }

                    continue;
                }

                if (is_file($path) && str_ends_with($path, '.blade.php')) {
                    $found[] = $path;
                }
            }
        }

        return $found;
    }
}
