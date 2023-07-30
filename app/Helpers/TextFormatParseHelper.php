<?php

namespace App\Helpers;

/**
 * Clase de utilidad para convertir entre formatos de archivos y html.
 */
class TextFormatParseHelper
{

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

    public static function getCodeRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._code', [
            'id' => $id,
            'data' => $data,
            'tunes' => $tunes,
        ])->render();
    }

    public static function getWarningRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._warning', [
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'tunes' => $tunes,
        ])->render();
    }

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

    public static function getListRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._list', [
            'id' => $id,
            'style' => $data['style'], // ordered, unordered
            'items' => $data['items'], // Array de items
            'tunes' => $tunes,
        ])->render();
    }

    public static function getCheckboxRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._checkbox', [
            'id' => $id,
            'items' => $data['items'], // Array de items
            'tunes' => $tunes,
        ])->render();
    }

    public static function getAttachesRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._attaches', [
            'id' => $id,
            'file' => $data['file'],
            'title' => $data['title'],
            'tunes' => $tunes,
        ])->render();
    }

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

    public static function getDelimiterRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._delimiter', [
            'id' => $id,
            'data' => $data,
            'tunes' => $tunes,
        ])->render();
    }

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

    public static function getWebPreviewRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._web_preview', [
            'id' => $id,
            'data' => $data,
            'link' => $data['link'],
            'title' => $data['meta']['title'],
            'description' => $data['meta']['description'] ?? '',
            'keywords' => $data['meta']['keywords'] ?? '',
            'image' => $data['image']['url'] ?? '',
        ])->render();
    }
    public static function getTableRaw(string $id, array $data, array $tunes): string
    {
        return view('editor.fields._table', [
            'id' => $id,
            'rows' => $data['rows'],
            'columns' => $data['columns'],
            'tunes' => $tunes,
        ])->render();
    }


    public static function getTemplates()
    {
        return [
            /*
            'embed' => function($service, $source, $embed, $width, $height, $caption) {
                return '<div class="embed"><iframe width="' . $width . '" height="' . $height . '" src="' . $embed . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></div>';
            },
            */
        ];
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

                default:
                    //$result[] = ''; // Plantear añadir <span>??
                    break;
            }



            $htmlRaw = implode(' ', $result);
            $html = preg_replace('/\\n|\\r|\\t/', '', $htmlRaw);
            $html = preg_replace('/\\s{2,}/', ' ', $html);


            dd('ENTRA', $block, $result, $html);

        }


        $htmlRaw = implode(' ', $result);

        $html = preg_replace('/\\n|\\r|\\t/', '', $htmlRaw);
        $html = preg_replace('/\\s{2,}/', ' ', $html);

        dd($html);

        dd('Checkpoint 1', $result);

        return implode(' ', $result);
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
}
