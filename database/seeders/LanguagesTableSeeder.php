<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use function array_merge;

/**
 * Class LanguagesTableSeeder
 *
 * @package Database\Seeders
 */
class LanguagesTableSeeder extends Seeder
{
    private $tableName = 'languages';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
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

        $now = Carbon::now();

        ## Recorre idiomas y los inserta solo cuando no existen.
        foreach ($datas as $data) {
            $exist = DB::table($this->tableName)
                ->where('iso2', $data['iso2'])
                ->first();

            if (! $exist) {
                DB::table($this->tableName)->insert(
                    array_merge($data, [
                        'created_at' => $now,
                    ])
                );
            }
        }
    }
}
