<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Collection;

/**
 * Clase de utilidad para convertir entre formatos de archivos y html.
 */
class TextFormatParseHelper
{
    /**
     * Devuelve la cadena con el peso formateado en la unidad de medida más apropiada.
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de código en bruto.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     */
    public static function getFieldRaw(string $id, array $data): string
    {
        return preg_replace('/\\n/', '', view('editor.fields._raw', [
            'id' => $id,
            'html' => is_string($data['html'] ?? null) ? $data['html'] : '',
        ])->render());
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de párrafo.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getParagraphRaw(string $id, array $data, array $tunes): string
    {

        // "Párrafo Normal<br>"
        // TODO: Quitar <br> al final del párrafo

        return view('editor.fields._paragraph', [
            'id' => $id,
            'text' => is_string($data['text'] ?? null) ? $data['text'] : '',
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de cabecera.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getHeaderRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._header', [
            'id' => $id,
            'text' => is_string($data['text'] ?? null) ? $data['text'] : '',
            // Sin nivel, el que pone el editor por defecto.
            'level' => min(6, max(1, (int) ($data['level'] ?? 3))),
            'tunes' => collect($tunes),
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de código.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getCodeRaw(string $id, array $data, array $tunes): string
    {
        // El código se enseña como texto: antes iba tal cual, así que un
        // `<div>` o un `<script>` dentro de un bloque de código se interpretaba
        // como HTML en la web en vez de verse.
        $code = (string) preg_replace('/\\r\\n|\\n|\\r/', '<br>', e(is_string($data['code'] ?? null) ? $data['code'] : ''));
        $nLines = substr_count($code, '<br>');

        return view('editor.fields._code', [
            'id' => $id,
            'data' => $data,
            'tunes' => $tunes,
            'code' => $code,
            'nLines' => $nLines,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un warning.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getWarningRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._warning', [
            'id' => $id,
            'title' => is_string($data['title'] ?? null) ? $data['title'] : '',
            'message' => is_string($data['message'] ?? null) ? $data['message'] : '',
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un quote.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getQuoteRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._quote', [
            'id' => $id,
            'text' => is_string($data['text'] ?? null) ? $data['text'] : '',
            'caption' => is_string($data['caption'] ?? null) ? $data['caption'] : '',
            'alignment' => ($data['alignment'] ?? 'left') === 'center' ? 'center' : 'left',
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un listado.
     *
     * Las listas de `@editorjs/list` 2.x pueden ser también de casillas
     * (`style: checklist`): se pintan igual que el bloque `checklist`.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getListRaw(string $id, array $data, array $tunes): string
    {
        $style = $data['style'] ?? 'unordered';
        $items = self::listItems($data['items'] ?? []);

        if ($style === 'checklist') {
            return view('editor.fields._checkbox', [
                'id' => $id,
                'items' => $items,
                'tunes' => $tunes,
            ])->render();
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        return view('editor.fields._list', [
            'id' => $id,
            'style' => $style === 'ordered' ? 'ordered' : 'unordered',
            'items' => $items,
            'start' => max(1, (int) ($meta['start'] ?? 1)),
            'counterType' => is_string($meta['counterType'] ?? null) ? $meta['counterType'] : 'numeric',
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un listado de checkboxs.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getCheckboxRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._checkbox', [
            'id' => $id,
            'items' => self::listItems($data['items'] ?? []),
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Elementos de una lista en un único formato, sea cual sea el que se guardó.
     *
     * - `@editorjs/list` 1.x (todas las listas de la v1): cadenas.
     * - `@editorjs/checklist`: `{text, checked}`.
     * - NestedList: `{content, items}`.
     * - `@editorjs/list` 2.x: `{content, meta, items}`, con `meta.checked` en
     *   las listas de casillas.
     *
     * @return list<array{content: string, checked: bool, items: list<array<string, mixed>>}>
     */
    public static function listItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $result = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $result[] = ['content' => $item, 'checked' => false, 'items' => []];

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $content = $item['content'] ?? $item['text'] ?? '';
            $meta = is_array($item['meta'] ?? null) ? $item['meta'] : [];

            $result[] = [
                'content' => is_string($content) ? $content : '',
                'checked' => (bool) ($meta['checked'] ?? $item['checked'] ?? false),
                'items' => self::listItems($item['items'] ?? []),
            ];
        }

        return $result;
    }

    /**
     * Número de un elemento de lista numerada en el estilo que eligió quien
     * escribe (`1`, `iv`, `IV`, `d`, `D`).
     */
    public static function listCounter(int $number, string $counterType): string
    {
        return match ($counterType) {
            'lower-roman' => strtolower(self::roman($number)),
            'upper-roman' => self::roman($number),
            'lower-alpha' => self::alpha($number),
            'upper-alpha' => strtoupper(self::alpha($number)),
            default => (string) $number,
        };
    }

    private static function roman(int $number): string
    {
        $symbols = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90,
            'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $roman = '';

        foreach ($symbols as $symbol => $value) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }

    /**
     * a, b… z, aa, ab…
     */
    private static function alpha(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(97 + $number % 26).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    /**
     * Devuelve el contenido HTML de un campo con adjuntos.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getAttachesRaw(string $id, array $data, array $tunes): string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $size = is_numeric($file['size'] ?? null) && (int) $file['size'] > 0
            ? self::formatBytes((int) $file['size'], 2)
            : null;

        // La subida de la v2 devuelve `url`, `name`, `size`, `extension` y
        // `file_id`; la de la v1 traía además el contenido, la miniatura y el
        // icono del tipo. La plantilla pintaba los de la v1 sin comprobarlos y
        // ninguna página con un adjunto nuevo se podía guardar.
        $name = (string) ($file['name'] ?? '');
        $title = (string) ($data['title'] ?? '');

        return view('editor.fields._attaches', [
            'id' => $id,
            'file' => $file,
            'url' => (string) ($file['url'] ?? ''),
            'name' => $name,
            'title' => $title !== '' ? $title : $name,
            'icon' => (string) ($file['file_type_image'] ?? ''),
            'tunes' => $tunes,
            'size' => $size,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para una imagen.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getImageRaw(string $id, array $data, array $tunes): string
    {
        $caption = $data['caption'] ?? '';
        $caption = preg_replace('/\\n|\\r|\<br\>/', '', $caption);

        // Las imágenes de la v1 traen las tres URL. Las que se suben desde el
        // editor de la v2 y las que vienen de Markdown o HTML sólo traen `url`,
        // y sin estas claves la vista reventaba al generar el HTML.
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $file['url'] ??= '';
        $file['url_thumbnail'] ??= $file['url'];
        $file['url_large'] ??= $file['url'];

        return view('editor.fields._image', [
            'id' => $id,
            'file' => $file,
            'caption' => $data['caption'] ?? '',
            'withBackground' => $data['withBackground'] ?? false,
            'withBorder' => $data['withBorder'] ?? false,
            'stretched' => $data['stretched'] ?? false,
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML con un delimitador.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getDelimiterRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._delimiter', [
            'id' => $id,
            'data' => $data,
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un alert.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getAlertRaw(string $id, array $data, array $tunes): string
    {
        $text = $data['text'] ?? $data['message'] ?? '';

        // Sin tipo o sin alineación: aviso informativo, a la izquierda.
        return view('editor.fields._alert', [
            'id' => $id,
            'type' => is_string($data['type'] ?? null) && $data['type'] !== '' ? $data['type'] : 'info',
            'align' => is_string($data['align'] ?? null) && $data['align'] !== '' ? $data['align'] : 'left',
            'text' => is_string($text) ? $text : '',
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo para previsualizar un sitio web.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getWebPreviewRaw(string $id, array $data, array $tunes): string
    {
        $link = (string) ($data['link'] ?? '');
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $title = (string) ($meta['title'] ?? '');
        $description = (string) ($meta['description'] ?? '');
        $image = (string) (is_array($meta['image'] ?? null) ? ($meta['image']['url'] ?? '') : '');

        // Muchas webs no se dejan leer (Cloudflare, bloqueos) y la tarjeta se
        // queda sólo con la dirección: un enlace normal.
        if ($title === '' && $description === '' && $image === '') {
            return view('editor.fields._link', ['id' => $id, 'link' => $link])->render();
        }

        return view('editor.fields._web_preview', [
            'id' => $id,
            'data' => $data,
            'link' => $link,
            'title' => $title !== '' ? $title : e((string) preg_replace('/^https?:\/\//', '', $link)),
            'description' => $description,
            'keywords' => $meta['keywords'] ?? '',
            'image' => $image,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido embebido.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getEmbedRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._embed', [
            'id' => $id,
            'service' => (string) ($data['service'] ?? ''), // youtube, vimeo, twitter, instagram, facebook, vine, vk
            'source' => (string) ($data['source'] ?? ''),
            'embed' => (string) ($data['embed'] ?? $data['source'] ?? ''),
            // Las medidas por defecto de la herramienta de vídeos.
            'width' => is_numeric($data['width'] ?? null) ? (int) $data['width'] : 580,
            'height' => is_numeric($data['height'] ?? null) ? (int) $data['height'] : 320,
            'caption' => (string) ($data['caption'] ?? ''),
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para una tabla.
     *
     * @param  string  $id  ID del campo.
     * @param  array  $data  Array con el contenido del campo.
     * @param  array  $tunes  Array con datos adicionales.
     */
    public static function getTableRaw(string $id, array $data, array $tunes): string
    {
        $hasHeader = (bool) ($data['withHeadings'] ?? false);
        $rows = array_values(array_filter(is_array($data['content'] ?? null) ? $data['content'] : [], 'is_array'));
        $columns = [];

        if ($hasHeader) {
            $columns = $rows[0] ?? [];
            unset($rows[0]);
        }

        return view('editor.fields._table', [
            'id' => $id,
            'hasHeader' => $hasHeader,
            'rows' => $rows,
            'columns' => $columns,
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Recibe un array de elementos y los prepara para devolver una estructura HTML
     *
     * @param  array  $blocks  Array de bloques para generar la estructura HTML.
     */
    public static function arrayToHtml(array $blocks): string
    {
        if (empty($blocks)) {
            return '';
        }

        $result = [];

        // TODO: Añadir Carousel https://github.com/mr8bit/carousel-editorjs
        // TODO: Añadir Galería https://gitlab.com/rodrigoodhin/editorjs-image-gallery
        // TODO: Añadir Diagramas: https://github.com/naduma/editorjs-mermaid

        // Cada plantilla aguanta que le falten datos y pinta lo que haya (A2 de
        // la auditoría de contenidos): un bloque incompleto no puede impedir
        // guardar la página. Los tipos que no tienen plantilla no se pintan;
        // que no se cuelen lo evita `ContentBlockValidator` al guardar.
        foreach ($blocks as $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                continue;
            }

            $id = (string) ($block['id'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $tunes = is_array($block['tunes'] ?? null) ? $block['tunes'] : [];

            $html = match ($block['type']) {
                'raw' => self::getFieldRaw($id, $data),
                'paragraph' => self::getParagraphRaw($id, $data, $tunes),
                'header' => self::getHeaderRaw($id, $data, $tunes),
                'code' => self::getCodeRaw($id, $data, $tunes),
                'warning' => self::getWarningRaw($id, $data, $tunes),
                'quote' => self::getQuoteRaw($id, $data, $tunes),
                'list' => self::getListRaw($id, $data, $tunes),
                'checklist' => self::getCheckboxRaw($id, $data, $tunes),
                'attaches' => self::getAttachesRaw($id, $data, $tunes),
                'image' => self::getImageRaw($id, $data, $tunes),
                'delimiter' => self::getDelimiterRaw($id, $data, $tunes),
                'table' => self::getTableRaw($id, $data, $tunes),
                'alert' => self::getAlertRaw($id, $data, $tunes),
                'linkTool' => self::getWebPreviewRaw($id, $data, $tunes),
                'embed' => self::getEmbedRaw($id, $data, $tunes),
                default => null,
            };

            if ($html !== null) {
                $result[] = $html;
            }
        }

        $htmlRaw = implode(' ', $result);

        $html = preg_replace('/\\n|\\r|\\t/', '', $htmlRaw);

        return preg_replace('/\\s{2,}/', ' ', $html);
    }

    /**
     * Recibe una cadena en formato JSON y devuelve un string en formato HTML.
     *
     * @param  string  $json  Cadena con formato JSON
     */
    public static function jsonToHtml(string $json): string
    {
        $jsonDecoded = json_decode($json, true);

        if (isset($jsonDecoded['blocks'])) {
            return self::arrayToHtml($jsonDecoded['blocks']);
        }

        return self::arrayToHtml($jsonDecoded);
    }

    /**
     * Busca en un array de bloques los elementos recibidos en otro array con los strings de campos a buscar.
     *
     * @param  array  $blocks  Es un array con todos los bloques del editor.
     * @param  array  $search  Es un array con cadenas de texto que coinciden con los bloques a buscar.
     */
    public static function searchBlocks(array $blocks, array $search): Collection
    {
        return collect(array_filter($blocks, function ($b) use ($search) {
            return in_array($b['type'], $search, false);
        }));
    }
}
