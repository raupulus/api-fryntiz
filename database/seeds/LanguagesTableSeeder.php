<?php

use Illuminate\Database\Seeder;

class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('languages')->insert([
            'code' => 'esp',
            'code2' => 'es',
            'name' => 'Español',
            'country' => 'España',
        ]);

        DB::table('languages')->insert([
            'code' => 'eng',
            'code2' => 'en',
            'name' => 'Inglés',
            'country' => 'Reino Unido',
        ]);
    }
}
