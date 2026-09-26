<?php

declare(strict_types=1);

namespace App\Services\Content;

use Dom\HTMLDocument;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Limpia el HTML de los textos de los bloques de Editor.js al guardar (B1 de
 * la auditoría de contenidos del 2026-09-24).
 *
 * Editor.js guarda el texto con su formato como trozos de HTML, y al abrir la
 * página el navegador los interpreta: un `<img src=x onerror=…>` metido en un
 * párrafo (por «JSON en crudo», por la API o desde una cuenta robada) se
 * ejecutaba en el navegador de quien abriera la página, con su sesión.
 *
 * Sólo quedan las etiquetas de formato que produce el propio editor: negrita,
 * cursiva, subrayado, tachado, enlace (`http`, `https`, `mailto`), código en
 * línea, resaltado y salto de línea; en el mensaje de la alerta, además `div`
 * y `p`. Lo demás se quita dejando su texto, salvo lo que no es texto
 * (`script`, `style`, `iframe`…), que se quita entero.
 *
 * Un texto que ya estaba limpio se devuelve **tal cual**, sin reescribirlo: así
 * las páginas que no traen nada raro no cambian ni un carácter (`<br>` no pasa a
 * `<br />`) y el HTML que se sirve sigue siendo el mismo.
 */
class ContentHtmlSanitizer
{
    /**
     * Clases que el editor pone en sus etiquetas en línea. Sin ellas, el
     * resaltado y el código en línea no se reconocen al volver a abrir la página.
     */
    private const INLINE_CLASSES = [
        'code' => ['inline-code'],
        'mark' => ['cdx-marker'],
    ];

    /**
     * Etiquetas de formato en línea que produce el editor.
     */
    private const INLINE_TAGS = ['b', 'strong', 'i', 'em', 'u', 's', 'br', 'a', 'code', 'mark'];

    /**
     * Etiquetas que no tienen texto que conservar: se quitan con lo de dentro.
     */
    private const DROPPED = [
        'script', 'style', 'template', 'noscript', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'svg', 'math', 'textarea', 'select', 'option', 'button', 'input', 'form', 'head', 'title',
    ];

    private ?HtmlSanitizer $inline = null;

    private ?HtmlSanitizer $alert = null;

    private ?HtmlSanitizer $text = null;

    /**
     * Texto con formato en línea (párrafo, título, elemento de lista, cita…).
     */
    public function inline(string $html): string
    {
        return $this->clean($this->inline ??= new HtmlSanitizer($this->config()), $html, self::INLINE_TAGS);
    }

    /**
     * Mensaje de la alerta: formato en línea y, además, `div` y `p`.
     */
    public function alertMessage(string $html): string
    {
        return $this->clean($this->alert ??= new HtmlSanitizer(
            $this->config()->allowElement('div')->allowElement('p'),
        ), $html, [...self::INLINE_TAGS, 'div', 'p']);
    }

    /**
     * Texto sin ninguna etiqueta (título y descripción de una tarjeta de enlace,
     * que se pintan tal cual en la web).
     */
    public function text(string $html): string
    {
        return $this->clean($this->text ??= new HtmlSanitizer($this->baseConfig()), $html, []);
    }

    /**
     * Los bloques con sus textos limpios. Los bloques `raw` (HTML libre, sólo
     * para administradores) y `code` (texto, no HTML) no se tocan.
     *
     * @param  array<mixed>  $blocks
     * @return array<mixed>
     */
    public function blocks(array $blocks): array
    {
        return array_map(fn ($block) => is_array($block) ? $this->block($block) : $block, $blocks);
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    public function block(array $block): array
    {
        if (! is_array($block['data'] ?? null)) {
            return $block;
        }

        $data = $block['data'];
        $inline = fn (mixed $value): mixed => is_string($value) ? $this->inline($value) : $value;

        switch ($block['type'] ?? null) {
            case 'paragraph':
            case 'header':
                $data['text'] = $inline($data['text'] ?? null);
                break;

            case 'list':
                if (is_array($data['items'] ?? null)) {
                    $data['items'] = $this->listItems($data['items']);
                }
                break;

            case 'checklist':
                if (is_array($data['items'] ?? null)) {
                    $data['items'] = array_map(function ($item) use ($inline) {
                        if (is_array($item) && is_string($item['text'] ?? null)) {
                            $item['text'] = $inline($item['text']);
                        }

                        return $item;
                    }, $data['items']);
                }
                break;

            case 'quote':
                $data['text'] = $inline($data['text'] ?? null);
                $data['caption'] = $inline($data['caption'] ?? null);
                break;

            case 'table':
                if (is_array($data['content'] ?? null)) {
                    $data['content'] = array_map(
                        fn ($row) => is_array($row) ? array_map($inline, $row) : $row,
                        $data['content'],
                    );
                }
                break;

            case 'alert':
                foreach (['message', 'text'] as $key) {
                    if (is_string($data[$key] ?? null)) {
                        $data[$key] = $this->alertMessage($data[$key]);
                    }
                }
                break;

            case 'warning':
                $data['title'] = $inline($data['title'] ?? null);
                $data['message'] = $inline($data['message'] ?? null);
                break;

            case 'image':
            case 'embed':
                $data['caption'] = $inline($data['caption'] ?? null);
                break;

            case 'attaches':
                $data['title'] = $inline($data['title'] ?? null);
                break;

            case 'linkTool':
                if (is_array($data['meta'] ?? null)) {
                    foreach (['title', 'description'] as $key) {
                        if (is_string($data['meta'][$key] ?? null)) {
                            $data['meta'][$key] = $this->text($data['meta'][$key]);
                        }
                    }
                }
                break;
        }

        // Las claves que no existían siguen sin existir: un `null` añadido
        // cambiaría el JSON guardado sin motivo.
        $block['data'] = array_filter(
            $data,
            fn ($value, $key): bool => $value !== null || array_key_exists($key, $block['data']),
            ARRAY_FILTER_USE_BOTH,
        );

        return $block;
    }

    /**
     * Elementos de lista en cualquiera de sus formatos (cadenas, NestedList o
     * `@editorjs/list` 2.x), con sus sublistas.
     *
     * @param  array<mixed>  $items
     * @return array<mixed>
     */
    private function listItems(array $items): array
    {
        return array_map(function ($item) {
            if (is_string($item)) {
                return $this->inline($item);
            }

            if (! is_array($item)) {
                return $item;
            }

            foreach (['content', 'text'] as $key) {
                if (is_string($item[$key] ?? null)) {
                    $item[$key] = $this->inline($item[$key]);
                }
            }

            if (is_array($item['items'] ?? null)) {
                $item['items'] = $this->listItems($item['items']);
            }

            return $item;
        }, $items);
    }

    /**
     * El texto limpio, o el original si limpiarlo no cambia nada de lo que es.
     *
     * Comparar árboles sólo vale si el original no trae etiquetas de fuera de
     * la lista: el analizador de HTML se come algunas al leerlas (un `<body
     * onload=…>` dentro de un párrafo pasa sus atributos al `<body>` del
     * documento y desaparece del trozo), y el original «parecería» limpio.
     *
     * @param  list<string>  $allowedTags
     */
    private function clean(HtmlSanitizer $sanitizer, string $html, array $allowedTags): string
    {
        // Sin «<» no hay etiquetas: no hay nada que quitar.
        if (! str_contains($html, '<')) {
            return $html;
        }

        $clean = $sanitizer->sanitize($html);

        if (! $this->onlyUses($html, $allowedTags)) {
            return $clean;
        }

        return $this->normalize($clean) === $this->normalize($html) ? $html : $clean;
    }

    /**
     * ¿Todas las etiquetas del texto están en la lista? Un comentario, un
     * `<!DOCTYPE>` o una instrucción `<?…>` cuentan como etiqueta de fuera.
     *
     * @param  list<string>  $allowedTags
     */
    private function onlyUses(string $html, array $allowedTags): bool
    {
        if (preg_match('/<[!?]/', $html) === 1) {
            return false;
        }

        preg_match_all('/<\/?\s*([a-zA-Z][^\s\/>]*)/', $html, $tags);

        return array_diff(array_map('strtolower', $tags[1]), $allowedTags) === [];
    }

    /**
     * El árbol del HTML, escrito siempre igual, para comparar dos trozos.
     */
    private function normalize(string $html): string
    {
        return HTMLDocument::createFromString(
            '<!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_NOERROR,
            'UTF-8',
        )->body->innerHTML;
    }

    private function config(): HtmlSanitizerConfig
    {
        return $this->baseConfig()
            ->allowElement('b')
            ->allowElement('strong')
            ->allowElement('i')
            ->allowElement('em')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('br')
            ->allowElement('a', ['href', 'target', 'rel'])
            ->allowElement('code', ['class'])
            ->allowElement('mark', ['class'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->withAttributeSanitizer(new class(self::INLINE_CLASSES) implements AttributeSanitizerInterface
            {
                /**
                 * @param  array<string, list<string>>  $classes
                 */
                public function __construct(private readonly array $classes) {}

                /**
                 * @return list<string>
                 */
                public function getSupportedElements(): array
                {
                    return array_keys($this->classes);
                }

                /**
                 * @return list<string>
                 */
                public function getSupportedAttributes(): array
                {
                    return ['class'];
                }

                public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
                {
                    $kept = array_intersect(preg_split('/\s+/', trim($value)) ?: [], $this->classes[$element] ?? []);

                    return $kept === [] ? null : implode(' ', $kept);
                }
            });
    }

    /**
     * Nada permitido: lo desconocido se quita dejando su texto y lo que no es
     * texto se quita entero. Sin límite de longitud: el de Symfony (20 000
     * caracteres) cortaría textos largos por la mitad.
     */
    private function baseConfig(): HtmlSanitizerConfig
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(-1);

        foreach (self::DROPPED as $element) {
            $config = $config->dropElement($element);
        }

        return $config;
    }
}
