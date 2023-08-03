<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

/**
 * Clase de utilidad para convertir entre formatos de archivos y html.
 */
class TextFormatParseHelper
{
    /**
     * Devuelve la cadena con el peso formateado en la unidad de medida más apropiada.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }


    /**
     * Devuelve el contenido HTML de un campo con contenido de código en bruto.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @return string
     */
    public static function getFieldRaw(string $id, array $data): string
    {
        return preg_replace('/\\n/', '', view('editor.fields._raw', [
            'id' => $id,
            'html' => $data['html'],
        ])->render());
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de párrafo.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     * @return string
     */
    public static function getParagraphRaw(string $id, array $data, array $tunes): string
    {

        // "Párrafo Normal<br>"
        // TODO: Quitar <br> al final del párrafo


        return view('editor.fields._paragraph', [
            'id' => $id,
            'text' => $data['text'],
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de cabecera.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getHeaderRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._header', [
            'id' => $id,
            'text' => $data['text'],
            'level' => $data['level'],
            'tunes' => collect($tunes),
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido de código.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getCodeRaw(string $id, array $data, array $tunes): string
    {
        $code = preg_replace('/\\n|\\r/', '<br>', $data['code']);
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
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getWarningRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._warning', [
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un quote.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getQuoteRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._quote', [
            'id' => $id,
            'text' => $data['text'],
            'caption' => $data['caption'],
            'alignment' => $data['alignment'], // left, center
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un listado.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getListRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._list', [
            'id' => $id,
            'style' => $data['style'], // ordered, unordered
            'items' => $data['items'], // Array de items
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para un listado de checkboxs.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getCheckboxRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._checkbox', [
            'id' => $id,
            'items' => $data['items'], // Array de items
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con adjuntos.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getAttachesRaw(string $id, array $data, array $tunes): string
    {
        $size = $data['file']['size'] ?? null;

        // Convertimos bytes a kb o megas o gigas
        if ($size && $size > 0) {
            $size = self::formatBytes($size, 2);
        }

        return view('editor.fields._attaches', [
            'id' => $id,
            'file' => $data['file'],
            'title' => $data['title'],
            'tunes' => $tunes,
            'size' => $size,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para una imagen.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getImageRaw(string $id, array $data, array $tunes): string
    {
        $caption = $data['caption'] ?? '';
        $caption = preg_replace('/\\n|\\r|\<br\>/', '', $caption);

        return view('editor.fields._image', [
            'id' => $id,
            'file' => $data['file'],
            'caption' => $data['caption'],
            'withBackground' => $data['withBackground'],
            'withBorder' => $data['withBorder'],
            'stretched' => $data['stretched'],
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML con un delimitador.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
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
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getAlertRaw(string $id, array $data, array $tunes): string
    {
        $text = $data['text'] ?? $data['message'];

        return view('editor.fields._alert', [
            'id' => $id,
            'type' => $data['type'],
            'align' => $data['align'],
            'text' => $text,
            'tunes' => $tunes,
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo para previsualizar un sitio web.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getWebPreviewRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._web_preview', [
            'id' => $id,
            'data' => $data,
            'link' => $data['link'],
            'title' => $data['meta']['title'],
            'description' => $data['meta']['description'] ?? '',
            'keywords' => $data['meta']['keywords'] ?? '',
            'image' => $data['meta']['image']['url'] ?? '',
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido embebido.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getEmbedRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._embed', [
            'id' => $id,
            'service' => $data['service'], // youtube, vimeo, twitter, instagram, facebook, vine, vk
            'source' => $data['source'],
            'embed' => $data['embed'],
            'width' => $data['width'],
            'height' => $data['height'],
            'caption' => $data['caption'],
        ])->render();
    }

    /**
     * Devuelve el contenido HTML de un campo con contenido para una tabla.
     *
     * @param string $id ID del campo.
     * @param array $data Array con el contenido del campo.
     * @param array $tunes Array con datos adicionales.
     *
     * @return string
     */
    public static function getTableRaw(string $id, array $data, array $tunes): string
    {
        $hasHeader = $data['withHeadings'] ?? false;
        $rows = $data['content'];
        $columns = [];

        if ($hasHeader) {
            $columns = $rows[0];
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
     * @param array $blocks Array de bloques para generar la estructura HTML.
     * @return string
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

        foreach ($blocks as $block) {

            switch ($block['type']) {
                case 'raw':
                    $result[] = self::getFieldRaw($block['id'], $block['data']);
                    break;
                case 'paragraph':
                    $result[] = self::getParagraphRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;
                case 'header':
                    $result[] = self::getHeaderRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;
                case 'code':
                    $result[] = self::getCodeRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;
                case 'warning':
                    $result[] = self::getWarningRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'quote':
                    $result[] = self::getQuoteRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'list':
                    $result[] = self::getListRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'checklist':
                    $result[] = self::getCheckboxRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'attaches':
                    $result[] = self::getAttachesRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'image':
                    $result[] = self::getImageRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'delimiter':
                    $result[] = self::getDelimiterRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'table':
                    $result[] = self::getTableRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'alert':
                    $result[] = self::getAlertRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'linkTool':
                    $result[] = self::getWebPreviewRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                case 'embed':
                    $result[] = self::getEmbedRaw($block['id'], $block['data'], isset($block['tunes']) ? $block['tunes'] : []);
                    break;

                default:
                    //$result[] = ''; // Plantear añadir <span>??
                    break;
            }


            //$htmlRaw = implode(' ', $result);
            //$html = preg_replace('/\\n|\\r|\\t/', '', $htmlRaw);
            //$html = preg_replace('/\\s{2,}/', ' ', $html);


            //dd('ENTRA', $block, $result, $html);

        }


        $htmlRaw = implode(' ', $result);

        $html = preg_replace('/\\n|\\r|\\t/', '', $htmlRaw);
        return preg_replace('/\\s{2,}/', ' ', $html);
    }

    /**
     * Recibe una cadena en formato JSON y devuelve un string en formato HTML.
     *
     * @param string $json Cadena con formato JSON
     * @return string
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
     * @param array $blocks Es un array con todos los bloques del editor.
     * @param array $search Es un array con cadenas de texto que coinciden con los bloques a buscar.
     *
     * @return Collection
     */
    public static function searchBlocks(array $blocks, array $search): Collection
    {
        return collect(array_filter($blocks, function($b) use ($search) {
            return in_array($b['type'], $search, false);
        }));
    }
}
