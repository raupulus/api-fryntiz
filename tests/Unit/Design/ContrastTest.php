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
    private const AA_TEXTO = 4.5;

    /**
     * Parejas fondo/texto que se usan en las vistas, con el sitio donde viven.
     *
     * @return array<string, array{string, string}>
     */
    public static function parejas(): array
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
    public static function parejasPorTema(): array
    {
        $casos = [];

        foreach (self::parejas() as $nombre => [$fondo, $texto]) {
            foreach (['claro', 'oscuro'] as $tema) {
                $casos["{$nombre} ({$tema})"] = [$tema, $fondo, $texto];
            }
        }

        return $casos;
    }

    #[Test]
    #[DataProvider('parejasPorTema')]
    public function cada_pareja_de_la_interfaz_llega_al_minimo_de_wcag(
        string $tema,
        string $fondo,
        string $texto,
    ): void {
        $tokens = $this->tokens($tema);

        $this->assertArrayHasKey($fondo, $tokens, "Falta el token --color-{$fondo} en el tema {$tema}.");
        $this->assertArrayHasKey($texto, $tokens, "Falta el token --color-{$texto} en el tema {$tema}.");

        $ratio = $this->ratio($tokens[$fondo], $tokens[$texto]);

        $this->assertGreaterThanOrEqual(
            self::AA_TEXTO,
            $ratio,
            sprintf(
                'En el tema %s, «%s» (%s) sobre «%s» (%s) da %s:1, por debajo del %s:1 que pide WCAG AA. '
                .'Comprueba que el token de texto está nombrado para ESE fondo: '
                .'`on-<algo>-container` va sobre `<algo>-container`, no sobre `<algo>-fixed`.',
                $tema, $texto, $tokens[$texto], $fondo, $tokens[$fondo], round($ratio, 2), self::AA_TEXTO,
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
    public function el_acento_del_tema_claro_sigue_por_debajo_de_aa(): void
    {
        $claro = $this->tokens('claro');
        $ratio = round($this->ratio($claro['surface'], $claro['on-tertiary-container']), 2);

        $this->assertSame(
            3.59,
            $ratio,
            'El contraste de `on-tertiary-container` sobre `surface` ha cambiado. '
            .'Si ya llega a 4,5:1, mueve la pareja a `parejas()` y borra este test.',
        );

        // En oscuro nunca ha sido un problema, y conviene que siga así.
        $oscuro = $this->tokens('oscuro');

        $this->assertGreaterThanOrEqual(
            self::AA_TEXTO,
            $this->ratio($oscuro['surface'], $oscuro['on-tertiary-container']),
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
    public function ningun_color_fijo_se_queda_sin_su_variante_oscura(): void
    {
        $culpables = [];

        foreach ($this->vistas() as $vista) {
            $huerfanos = $this->coloresHuerfanos($vista);

            if ($huerfanos !== []) {
                $culpables[str_replace(base_path().'/', '', $vista)] = $huerfanos;
            }
        }

        // Excepciones justificadas: colores sólidos que se pintan sobre un
        // fondo propio y por tanto no dependen del tema de la página.
        $permitidos = [
            // Botón verde sólido con texto blanco encima.
            'resources/views/components/button.blade.php',
            'resources/views/newsletter/manage.blade.php',
            // Barras de progreso de colores, sobre su propio carril.
            'resources/views/hardware/energy/index.blade.php',
            // Iconos y textos sobre cabeceras de color sólido.
            'resources/views/keycounter/index.blade.php',
        ];

        foreach ($permitidos as $permitido) {
            unset($culpables[$permitido]);
        }

        $this->assertSame(
            [],
            $culpables,
            'Estas vistas usan un color fijo de Tailwind sin variante `dark:`, así que no '
            .'cambia con el tema. Usa un token del sistema, o añade la variante si el color '
            .'va sobre un fondo sólido propio: '
            .json_encode($culpables, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Clases de color fijo sin pareja `dark:` de la misma propiedad.
     *
     * @return list<string>
     */
    private function coloresHuerfanos(string $vista): array
    {
        $paleta = 'red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue'
            .'|indigo|violet|purple|fuchsia|pink|rose';
        $patron = '/(?P<dark>dark:)?(?P<prop>bg|text|border)-(?:'.$paleta.')-\d{2,3}(?:\/\d+)?/';

        $contenido = (string) file_get_contents($vista);

        // Los comentarios Blade explican precisamente estos casos; citarlos no
        // puede hacer fallar el test.
        $contenido = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $contenido);

        $huerfanos = [];

        foreach (explode("\n", $contenido) as $numero => $linea) {
            if (preg_match_all($patron, $linea, $coincidencias, PREG_SET_ORDER) === 0) {
                continue;
            }

            $conVarianteOscura = [];
            $sinVariante = [];

            foreach ($coincidencias as $clase) {
                if (($clase['dark'] ?? '') !== '') {
                    $conVarianteOscura[$clase['prop']] = true;
                } else {
                    $sinVariante[] = [$clase['prop'], $clase[0]];
                }
            }

            foreach ($sinVariante as [$propiedad, $clase]) {
                if (! isset($conVarianteOscura[$propiedad])) {
                    $huerfanos[] = ($numero + 1).': '.$clase;
                }
            }
        }

        return $huerfanos;
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
    private function tokens(string $tema): array
    {
        static $cache = [];

        if (isset($cache[$tema])) {
            return $cache[$tema];
        }

        $css = (string) file_get_contents(base_path('resources/css/app.css'));

        $claro = $this->tokensDelBloque($css, '@theme');

        if ($tema === 'claro') {
            return $cache[$tema] = $claro;
        }

        return $cache[$tema] = array_merge($claro, $this->tokensDelBloque($css, 'html.dark'));
    }

    /**
     * @return array<string, string>
     */
    private function tokensDelBloque(string $css, string $selector): array
    {
        $inicio = mb_strpos($css, $selector.' {');

        $this->assertNotFalse($inicio, "No se encuentra el bloque {$selector} en app.css.");

        $fin = mb_strpos($css, "\n}", $inicio);
        $bloque = mb_substr($css, $inicio, $fin - $inicio);

        preg_match_all('/--color-([a-z0-9-]+):\s*(#[0-9a-fA-F]{6})\s*;/', $bloque, $m, PREG_SET_ORDER);

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
        $la = $this->luminancia($a);
        $lb = $this->luminancia($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Luminancia relativa, tal cual la define WCAG.
     */
    private function luminancia(string $hex): float
    {
        $canales = [];

        foreach ([1, 3, 5] as $posicion) {
            $valor = hexdec(substr($hex, $posicion, 2)) / 255;

            $canales[] = $valor <= 0.03928
                ? $valor / 12.92
                : (($valor + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];
    }

    /**
     * Las vistas del frontend público, que son las que tienen los dos temas.
     *
     * Fuera `scribe` (documentación generada), `filament` (usa su propio tema y
     * sus utilidades) y `vendor` (vistas publicadas de terceros).
     *
     * @return list<string>
     */
    private function vistas(): array
    {
        $pendientes = [base_path('resources/views')];
        $encontradas = [];
        $excluidos = ['/scribe', '/filament', '/vendor', '/editor'];

        while ($pendientes !== []) {
            $directorio = array_pop($pendientes);

            foreach ((array) glob($directorio.'/*') as $ruta) {
                if (is_dir($ruta)) {
                    foreach ($excluidos as $excluido) {
                        if (str_ends_with((string) $ruta, $excluido)) {
                            continue 2;
                        }
                    }

                    $pendientes[] = $ruta;

                    continue;
                }

                if (is_file($ruta) && str_ends_with((string) $ruta, '.blade.php')) {
                    $encontradas[] = (string) $ruta;
                }
            }
        }

        return $encontradas;
    }
}
