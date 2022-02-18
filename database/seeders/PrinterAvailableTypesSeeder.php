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
    private $tableName = 'printer_available_types';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
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

        foreach ($datas as $data) {
            $exist = DB::table($this->tableName)
                ->where('slug', $data['slug'])
                ->first();

            if (! $exist) {
                DB::table($this->tableName)->insert([
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }
}
