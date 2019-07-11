<?php

use App\Translation;

class TranslationHelper
{
    public static function newToken()
    {
        // Buscar si hay alguno
        $ultimo = Translation::whereNull('deleted_at')
                    ->orderBy('created_at', 'DESC')
                    ->first();
    }
}
