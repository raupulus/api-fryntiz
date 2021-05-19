<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'code' => 'esp',
                'code2' => 'es',
                'name' => 'Español',
                'country' => 'España',
            ],
            [
                'code' => 'eng',
                'code2' => 'en',
                'name' => 'Inglés',
                'country' => 'Reino Unido',
            ]
        ];

        ## Recorre idiomas y los inserta solo cuando no existen.
        foreach ($languages as $lang) {
            $exist = DB::table('languages')
                ->where('code', $lang['code'])
                ->first();

            if (!$exist) {
                DB::table('languages')->insert($lang);
            }
        }
    }
}
