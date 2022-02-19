<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class ReferredPlatformsSeeder
 */
class ReferredPlatformsSeeder extends Seeder
{
    private $tableName = 'referred_platforms';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            [
                'name' => 'Amazon',
                'slug' => 'amazon',
                'description' => 'Plataforma de referidos de Amazon',
                'url' => 'https://afiliados.amazon.es',
                'url_panel' => 'https://afiliados.amazon.es/home',
                'url_register' => 'https://afiliados.amazon.es/signup',
            ]

        ];

        $now = Carbon::now();

        foreach ($datas as $data) {
            $exist = DB::table($this->tableName)
                ->where('slug', $data['slug'])
                ->first();

            if (! $exist) {
                DB::table($this->tableName)->insert(
                    array_merge($data, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }
        }
    }
}
