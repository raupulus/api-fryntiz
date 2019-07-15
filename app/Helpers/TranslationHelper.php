<?php

use App\Translation;

class TranslationHelper
{
    /**
     * Genera un nuevo token y lo devuelve.
     */
    public static function newToken()
    {
        return self::lastToken() + 1;
    }

    /**
     * Encuentra el último valor de token introducido para la tabla de
     * traducciones.
     */
    public static function lastToken()
    {
        $translation = Translation::whereNull('deleted_at')
            ->orderBy('token', 'DESC')
            ->first();

        return $translation->token;
    }
}
