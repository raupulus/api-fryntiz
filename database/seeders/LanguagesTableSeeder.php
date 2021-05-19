<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class LanguagesTableSeeder
 *
 * @package Database\Seeders
 */
class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $languages = [
            [
                'locale' => 'es_ES',
                'iso_locale' => 'es-ES',
                'iso2' => 'es',
                'iso3' => 'esp',
                'name' => 'Español',
                'iso_language' => 'spanish',
                'icon16' => 'img/languages/16_es.png',
                'icon32' => 'img/languages/32_es.png',
                'icon64' => 'img/languages/64_es.png',
            ],
            [
                'locale' => 'en_EN',
                'iso_locale' => 'en-EN',
                'iso2' => 'en',
                'iso3' => 'eng',
                'name' => 'English',
                'iso_language' => 'english',
                'icon16' => 'img/languages/16_en.png',
                'icon32' => 'img/languages/32_en.png',
                'icon64' => 'img/languages/64_en.png',
            ]
        ];

        ## Recorre idiomas y los inserta solo cuando no existen.
        foreach ($languages as $lang) {
            $exist = DB::table('languages')
                ->where('iso2', $lang['iso2'])
                ->first();

            if (!$exist) {
                DB::table('languages')->insert($lang);
            }
        }
    }
}
