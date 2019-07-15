<?php

namespace App;

class Translation extends MinModel
{
    protected $table = 'translations';

    public static $rules = [

    ];

    public static function findToken($token)
    {
        $translations = Translation::whereNull('deleted_at')
            ->where('token', $token)
            ->orderBy('token', 'DESC')
            ->first();

        return $translations;
    }
}
