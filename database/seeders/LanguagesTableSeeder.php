<?php

namespace Database\Seeders;

use Carbon\Carbon;
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
                'icon16' => 'images/icons/flags/16x16/es.png',
                'icon32' => 'images/icons/flags/32x32/es.png',
                'icon64' => 'images/icons/flags/64x64/es.png',
                'created_at' => Carbon::now(),
            ],
            [
                'locale' => 'en_EN',
                'iso_locale' => 'en-EN',
                'iso2' => 'en',
                'iso3' => 'eng',
                'name' => 'English',
                'iso_language' => 'english',
                'icon16' => 'images/icons/flags/16x16/en.png',
                'icon32' => 'images/icons/flags/32x32/en.png',
                'icon64' => 'images/icons/flags/64x64/en.png',
                'created_at' => Carbon::now(),
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
