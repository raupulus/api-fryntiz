<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class PrinterAvailableTypesSeeder
 */
class PrinterAvailableTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $repositories = [
            [
                'name' => '2D',
                'slug' => '2d',
                'description' => 'Impresora 2D común',
            ],
            [
                'name' => '3D',
                'slug' => '3d',
                'description' => 'Impresora 3D',
            ],
            [
                'name' => 'Térmica',
                'slug' => 'thermal',
                'description' => 'Impresora térmica',
            ],
            [
                'name' => 'Tickets',
                'slug' => 'ticket',
                'description' => 'Impresora de tiques',
            ],
        ];

        $now = Carbon::now();

        foreach ($repositories as $repository) {
            $exist = DB::table('printer_available_types')
                ->where('slug', $repository['slug'])
                ->first();

            if (! $exist) {
                DB::table('cv_available_repository_types')->insert([
                    'name' => $repository['name'],
                    'slug' => $repository['slug'],
                    'description' => $repository['description'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }
}
