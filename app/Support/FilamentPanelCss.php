<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Foundation\Vite;
use Throwable;

/**
 * La etiqueta que carga el CSS propio de los paneles Filament.
 *
 * El fichero es `resources/css/filament/admin/panel.css`, compilado por Vite, y
 * los dos PanelProviders lo inyectan en `HEAD_END` con esta clase. Está aquí y
 * no repetido en cada provider para que la ruta de la entrada viva en un sitio.
 *
 * **Por qué Vite y no otra cosa:**
 *
 * - `viteTheme()` recompila el tema entero de Filament: cambiaría el aspecto de
 *   todo el panel, no sólo añadiría lo nuestro.
 * - `FilamentAsset::register()` publica en `public/css` con `composer install`,
 *   que no va versionado y que un `git pull` no trae.
 * - `public/build` sí va versionado, así que esto llega al servidor solo.
 */
final class FilamentPanelCss
{
    /**
     * Entrada de Vite. Tiene que estar también en `vite.config.js`.
     */
    public const ENTRADA = 'resources/css/filament/admin/panel.css';

    /**
     * El `<link>` del CSS propio, o nada si no se puede resolver.
     *
     * **Un CSS que falta no puede tumbar el panel.** Si alguien añade la entrada
     * y no compila, Vite lanza una excepción al no encontrarla en el manifest, y
     * sin este `catch` cada página del panel devolvería un 500. Se registra y
     * el panel sigue funcionando sin los estilos propios; el test
     * `FilamentPanelCssTest` es el que avisa antes de llegar ahí.
     */
    public static function etiqueta(): string
    {
        try {
            return app(Vite::class)(self::ENTRADA)->toHtml();
        } catch (Throwable $e) {
            report($e);

            return '';
        }
    }
}
