<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

use function mb_substr;
use function preg_replace;
use function strip_tags;
use function trim;

/**
 * HTML escrito por una persona, servido sin abrir la puerta a nadie.
 *
 * Las descripciones que se escriben desde la intranet —la de una planta de
 * SmartPlant, por ejemplo— salían con `{{ }}`, que escapa las etiquetas, así
 * que un `<p>` se veía literalmente como «&lt;p&gt;». Y la solución fácil,
 * `{!! !!}` a secas, convierte cualquier campo de texto de la intranet en un
 * XFS almacenado: basta con que alguien con acceso al panel —o cualquier
 * importación de datos de la v1— cuele un `<script>`.
 *
 * En medio está esto: una lista blanca de etiquetas de formato, sin atributos
 * salvo el `href` de los enlaces, y con los esquemas de URL acotados.
 *
 * `strip_tags()` con una lista de etiquetas **no** sirve para esto: deja pasar
 * los atributos, así que `<p onclick="…">` y `<a href="javascript:…">` cruzan
 * enteros. Por eso se usa `symfony/html-sanitizer`, que analiza el árbol.
 */
class HtmlHelper
{
    /**
     * Etiquetas de formato admitidas en un texto de la intranet.
     *
     * Sin `<img>` ni `<iframe>` a propósito: para eso está el editor de
     * contenido, que sube los ficheros al módulo de imágenes y los sirve por su
     * controlador. Meterlos aquí sería aceptar `src` arbitrarios.
     *
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'div', 'span',
        'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li',
        'h3', 'h4', 'h5', 'h6',
        'blockquote', 'code', 'pre',
        'a',
    ];

    /**
     * Limpia un HTML de la intranet y lo deja listo para pintar con `{!! !!}`.
     *
     * Devuelve un `HtmlString`, así que Blade no lo vuelve a escapar.
     */
    public static function safeBasic(?string $html): HtmlString
    {
        if ($html === null || trim($html) === '') {
            return new HtmlString('');
        }

        return new HtmlString(self::sanitizer()->sanitize($html));
    }

    /**
     * El mismo texto, pero en plano y recortado: para una `<meta>`.
     *
     * Las etiquetas `description`, `og:description` y `twitter:description` no
     * admiten HTML, y meterlo ahí sale en el resultado de búsqueda tal cual.
     */
    public static function toMetaDescription(?string $html, int $limit = 160): string
    {
        if ($html === null) {
            return '';
        }

        // `strip_tags` basta aquí: lo que quede va dentro de un atributo, que
        // Blade escapa igualmente. Lo que se busca es texto legible.
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return trim(mb_substr($text, 0, $limit - 1)).'…';
    }

    /**
     * El saneador, configurado una sola vez por petición.
     */
    private static function sanitizer(): HtmlSanitizer
    {
        static $sanitizer = null;

        if ($sanitizer instanceof HtmlSanitizer) {
            return $sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            // De cero: `allowSafeElements()` trae una lista larga que incluye
            // cosas que aquí no pintan nada.
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            // Sin host permitido explícitamente, se aceptan todos los enlaces
            // externos; lo que importa es el esquema.
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        foreach (self::ALLOWED_TAGS as $tag) {
            $config = $config->allowElement($tag);
        }

        // El único atributo que sobrevive. Sin `target`: que un enlace decida
        // abrir una pestaña nueva es cosa de quien diseña la página, no de
        // quien escribe la descripción.
        $config = $config->allowAttribute('href', ['a']);

        return $sanitizer = new HtmlSanitizer($config);
    }
}
