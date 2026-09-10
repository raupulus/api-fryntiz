<?php

declare(strict_types=1);

namespace Tests\Unit\Design;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function base_path;
use function file_get_contents;
use function hexdec;
use function max;
use function min;
use function preg_match_all;
use function round;
use function sprintf;
use function substr;

/**
 * El contraste de la paleta, medido en vez de mirado.
 *
 * Los dos fallos que se reportaron en `/smartplant` eran de este tipo y ninguno
 * se ve leyendo el CSS:
 *
 *  - `on-tertiary-container` (#cce5ff en oscuro) sobre `tertiary-fixed`
 *    (#ffdcc6): **1,01:1**, o sea texto invisible. Venía de emparejar un token
 *    `on-<algo>-container` con un fondo `<algo>-fixed`, que no es el suyo.
 *  - Los estados con `bg-green-100` y `text-green-700`, colores fijos de
 *    Tailwind sin variante oscura.
 *
 * Comprobar esto a ojo en dos temas no escala. Aquí se leen los tokens del CSS
 * y se calcula el ratio WCAG de cada pareja que la interfaz usa de verdad.
 *
 * **Si añades una pareja de fondo y texto a una vista, añádela aquí.**
 */
class ContrastTest extends TestCase
{
    /** Mínimo de WCAG AA para texto normal. */
    private const AA_TEXT = 4.5;

    /**
     * Parejas fondo/texto que se usan en las vistas, con el sitio donde viven.
     *
     * @return array<string, array{string, string}>
     */
    public static function pairs(): array
    {
        return [
            // SmartPlant: estados encendidos (riego activo, tanque lleno…).
            'estado encendido' => ['success-container', 'on-success-container'],

            // SmartPlant: badges e icono de «Hardware del proyecto».
            'badge de tecnología' => ['tertiary-fixed', 'on-tertiary-fixed'],

            // Texto general sobre las tres superficies del sistema.
            'texto sobre surface' => ['surface', 'on-surface'],
            'texto sobre tarjeta' => ['surface-container-lowest', 'on-surface'],
            'texto sobre sección' => ['surface-container-low', 'on-surface'],
            'texto secundario sobre surface' => ['surface', 'on-surface-variant'],
            'texto secundario sobre tarjeta' => ['surface-container-lowest', 'on-surface-variant'],
            'texto secundario sobre contenedor' => ['surface-container', 'on-surface-variant'],

            // Footer y bloques invertidos.
            'texto invertido' => ['inverse-surface', 'inverse-on-surface'],

            // Botones primarios.
            'botón primario' => ['primary-container', 'on-primary'],
        ];
    }

    /**
     * @return list<array{string, string, string}>
     */
    public static function pairsByTheme(): array
    {
        $cases = [];

        foreach (self::pairs() as $name => [$background, $text]) {
            foreach (['claro', 'oscuro'] as $theme) {
                $cases["{$name} ({$theme})"] = [$theme, $background, $text];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('pairsByTheme')]
    public function each_ui_pair_reaches_the_wcag_minimum(
        string $theme,
        string $background,
        string $text,
    ): void {
        $tokens = $this->tokens($theme);

        $this->assertArrayHasKey($background, $tokens, "Falta el token --color-{$background} en el tema {$theme}.");
        $this->assertArrayHasKey($text, $tokens, "Falta el token --color-{$text} en el tema {$theme}.");

        $ratio = $this->ratio($tokens[$background], $tokens[$text]);

        $this->assertGreaterThanOrEqual(
            self::AA_TEXT,
            $ratio,
            sprintf(
                'En el tema %s, «%s» (%s) sobre «%s» (%s) da %s:1, por debajo del %s:1 que pide WCAG AA. '
                .'Comprueba que el token de texto está nombrado para ESE fondo: '
                .'`on-<algo>-container` va sobre `<algo>-container`, no sobre `<algo>-fixed`.',
                $theme, $text, $tokens[$text], $background, $tokens[$background], round($ratio, 2), self::AA_TEXT,
            ),
        );
    }

    /**
     * Deuda conocida, con su número.
     *
     * `on-tertiary-container` sobre `surface` da 3,59:1 en el tema claro:
     * cumple AA para texto grande (3:1) pero no para el `text-xs font-bold` con
     * el que se usa en `home`, `weather_station` y `airflight`. En oscuro está
     * perfecto.
     *
     * No se arregla aquí porque es **el color de acento de toda la plataforma**
     * y bajarle la luminosidad cambia cómo se ve el sitio entero: es una
     * decisión de identidad visual. Ver `docs/info/DESIGN.md`.
     *
     * Este test **falla el día que se arregle**, para que se mueva a la lista de
     * arriba y deje de estar en deuda.
     */
    #[Test]
    public function the_light_theme_accent_is_still_below_aa(): void
    {
        $light = $this->tokens('claro');
        $ratio = round($this->ratio($light['surface'], $light['on-tertiary-container']), 2);

        $this->assertSame(
            3.59,
            $ratio,
            'El contraste de `on-tertiary-container` sobre `surface` ha cambiado. '
            .'Si ya llega a 4,5:1, mueve la pareja a `parejas()` y borra este test.',
        );

        // En oscuro nunca ha sido un problema, y conviene que siga así.
        $dark = $this->tokens('oscuro');

        $this->assertGreaterThanOrEqual(
            self::AA_TEXT,
            $this->ratio($dark['surface'], $dark['on-tertiary-container']),
        );
    }

    /**
     * Un color fijo de Tailwind sin variante `dark:` no cambia con el tema.
     *
     * `bg-green-100` se queda claro en el tema oscuro, y si encima la etiqueta
     * usa `on-surface-variant` —que en oscuro es un color claro— queda claro
     * sobre claro. Era «Riego activo» en `/smartplant`.
     *
     * Lo que se busca aquí es la clase **huérfana**: una de color fijo cuya
     * propiedad (`bg`, `text` o `border`) no tiene su pareja `dark:` en la
     * misma línea. Un `bg-amber-50 dark:bg-amber-950` está declarado a
     * propósito y funciona; lo que rompe es el que se queda solo.
     *
     * Los tokens del sistema siguen siendo lo preferible: esto es la red de
     * seguridad, no el criterio.
     */
    #[Test]
    public function no_fixed_color_is_left_without_its_dark_variant(): void
    {
        $culprits = [];

        foreach ($this->views() as $view) {
            $orphans = $this->orphanColors($view);

            if ($orphans !== []) {
                $culprits[str_replace(base_path().'/', '', $view)] = $orphans;
            }
        }

        // Excepciones justificadas: colores sólidos que se pintan sobre un
        // fondo propio y por tanto no dependen del tema de la página.
        $allowed = [
            // Botón verde sólido con texto blanco encima.
            'resources/views/components/button.blade.php',
            'resources/views/newsletter/manage.blade.php',
            // Barras de progreso de colores, sobre su propio carril.
            'resources/views/hardware/energy/index.blade.php',
            // Iconos y textos sobre cabeceras de color sólido.
            'resources/views/keycounter/index.blade.php',
        ];

        foreach ($allowed as $exception) {
            unset($culprits[$exception]);
        }

        $this->assertSame(
            [],
            $culprits,
            'Estas vistas usan un color fijo de Tailwind sin variante `dark:`, así que no '
            .'cambia con el tema. Usa un token del sistema, o añade la variante si el color '
            .'va sobre un fondo sólido propio: '
            .json_encode($culprits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Clases de color fijo sin pareja `dark:` de la misma propiedad.
     *
     * @return list<string>
     */
    private function orphanColors(string $view): array
    {
        $palette = 'red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue'
            .'|indigo|violet|purple|fuchsia|pink|rose';
        $pattern = '/(?P<dark>dark:)?(?P<prop>bg|text|border)-(?:'.$palette.')-\d{2,3}(?:\/\d+)?/';

        $content = (string) file_get_contents($view);

        // Los comentarios Blade explican precisamente estos casos; citarlos no
        // puede hacer fallar el test.
        $content = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $content);

        $orphans = [];

        foreach (explode("\n", $content) as $number => $line) {
            if (preg_match_all($pattern, $line, $matches, PREG_SET_ORDER) === 0) {
                continue;
            }

            $withDarkVariant = [];
            $withoutVariant = [];

            foreach ($matches as $class) {
                if (($class['dark'] ?? '') !== '') {
                    $withDarkVariant[$class['prop']] = true;
                } else {
                    $withoutVariant[] = [$class['prop'], $class[0]];
                }
            }

            foreach ($withoutVariant as [$property, $class]) {
                if (! isset($withDarkVariant[$property])) {
                    $orphans[] = ($number + 1).': '.$class;
                }
            }
        }

        return $orphans;
    }

    // ── Utilidades ───────────────────────────────────────────────────────────

    /**
     * Los tokens de color de un tema, leídos del CSS.
     *
     * El tema oscuro sólo redefine algunos, así que hereda del claro lo que no
     * declara, igual que en el navegador.
     *
     * @return array<string, string>
     */
    private function tokens(string $theme): array
    {
        static $cache = [];

        if (isset($cache[$theme])) {
            return $cache[$theme];
        }

        $css = (string) file_get_contents(base_path('resources/css/app.css'));

        $light = $this->tokensFromBlock($css, '@theme');

        if ($theme === 'claro') {
            return $cache[$theme] = $light;
        }

        return $cache[$theme] = array_merge($light, $this->tokensFromBlock($css, 'html.dark'));
    }

    /**
     * @return array<string, string>
     */
    private function tokensFromBlock(string $css, string $selector): array
    {
        $start = mb_strpos($css, $selector.' {');

        $this->assertNotFalse($start, "No se encuentra el bloque {$selector} en app.css.");

        $end = mb_strpos($css, "\n}", $start);
        $block = mb_substr($css, $start, $end - $start);

        preg_match_all('/--color-([a-z0-9-]+):\s*(#[0-9a-fA-F]{6})\s*;/', $block, $m, PREG_SET_ORDER);

        $tokens = [];

        foreach ($m as $token) {
            $tokens[$token[1]] = $token[2];
        }

        return $tokens;
    }

    /**
     * Ratio de contraste de WCAG 2.1 entre dos colores.
     */
    private function ratio(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Luminancia relativa, tal cual la define WCAG.
     */
    private function luminance(string $hex): float
    {
        $channels = [];

        foreach ([1, 3, 5] as $position) {
            $value = hexdec(substr($hex, $position, 2)) / 255;

            $channels[] = $value <= 0.03928
                ? $value / 12.92
                : (($value + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Las vistas del frontend público, que son las que tienen los dos temas.
     *
     * Fuera `scribe` (documentación generada), `filament` (usa su propio tema y
     * sus utilidades) y `vendor` (vistas publicadas de terceros).
     *
     * @return list<string>
     */
    private function views(): array
    {
        $pending = [base_path('resources/views')];
        $found = [];
        $excluded = ['/scribe', '/filament', '/vendor', '/editor'];

        while ($pending !== []) {
            $directory = array_pop($pending);

            foreach ((array) glob($directory.'/*') as $path) {
                if (is_dir($path)) {
                    foreach ($excluded as $exclusion) {
                        if (str_ends_with((string) $path, $exclusion)) {
                            continue 2;
                        }
                    }

                    $pending[] = $path;

                    continue;
                }

                if (is_file($path) && str_ends_with((string) $path, '.blade.php')) {
                    $found[] = (string) $path;
                }
            }
        }

        return $found;
    }
}
