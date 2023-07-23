<?php

namespace App\Helpers;

/**
 * Clase de utilidad para convertir entre formatos de archivos y html.
 */
class TextFormatParseHelper
{
    public static function getTemplates()
    {
        return [
            'raw' => function($html) {
                return $html;
            },
            'header' => function($text, $level) {
                return "<h{$level}>{$text}</h{$level}>";
            },
            'paragraph' => function($text) {
                return "<p>{$text}</p>";
            },
            'image' => function($file, $caption, $widthBorder, $stretched, $withBackground) {
                return "<img src=\"{$file['url']}\" title=\"{$caption}\" alt=\"{$caption}\">";
            },
            'quote' => function($text, $caption, $aligment) {
                return "<blockquote><p>${text}</p><span>${caption}</span></blockquote>";
            },
            'embed' => function($service, $source, $embed, $width, $height, $caption) {
                return '<div class="embed"><iframe width="' . $width . '" height="' . $height . '" src="' . $embed . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></div>';
            },
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
        return '';

        if (empty($blocks)) {
            return '';
        }

        $result = [];

        $templates = self::getTemplates();

        foreach ($blocks as $block) {
            if (array_key_exists($block['type'], $templates)) {
                $template = $templates[$block['type']];
                $data = $block['data'];
                $result[] = call_user_func_array($template, $data);
            }
        }

        return implode($result);
    }

    /**
     * Recibe una cadena en formato JSON y devuelve un string en formato HTML.
     *
     * @param string $json Cadena con formato JSON
     * @return string
     */
    public static function jsonToHtml(string $json): string
    {
        return '';

        $jsonDecoded = json_decode($json, true);

        if (isset($jsonDecoded['blocks'])) {
            return self::arrayToHtml($jsonDecoded['blocks']);
        }

        return self::arrayToHtml($jsonDecoded);
    }
}
