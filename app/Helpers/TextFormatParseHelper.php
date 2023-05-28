<?php

namespace App\Helpers;

/**
 * Clase de utilidad para convertir entre formatos de archivos y html.
 */
class TextFormatParseHelper
{

    /**
     * Recibe un array de elementos y los prepara para devolver una estructura HTML
     *
     * @param array $array Array de bloques para generar la estructura HTML.
     * @return string
     */
    public static function arrayToHtml(array $array): string
    {
        if (empty($array)) {
            return '';
        }


        // TODO Parsear estructura

        foreach ($array as $block) {

        }




        return '<div></div>';
    }

    /**
     * Recibe una cadena en formato JSON y devuelve un string en formato HTML.
     *
     * @param string $json Cadena con formato JSON
     * @return string
     */
    public static function jsonToHtml(string $json): string
    {
        return self::arrayToHtml(json_decode($json, true));
    }
}
