<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use function bcrypt;

/**
 * Class HardwareAvailableComponentsTableSeeder
 *
 * @package Database\Seeders
 */
class HardwareAvailableComponentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $componentsAvailable = [
            [
                'type' => 'motherboard',
                'slug' => 'motherboard',
                'name' => 'Placa Base',
            ],
            [
                'type' => 'cpu',
                'slug' => 'processor',
                'name' => 'Procesador',
            ],
            [
                'type' => 'ram',
                'slug' => 'memory-ram',
                'name' => 'Memoria RAM',
            ],
            [
                'type' => 'hdd',
                'slug' => 'hard-drive',
                'name' => 'Disco duro',
            ],
            [
                'type' => 'ssd',
                'slug' => 'solid-drive',
                'name' => 'Disco Sólido',
            ],
            [
                'type' => 'gpu',
                'slug' => 'graphic-card',
                'name' => 'Tarjeta Gráfica',
            ],
            [
                'type' => 'power',
                'slug' => 'power-supply',
                'name' => 'Fuente de Alimentación',
            ],
            [
                'type' => 'network',
                'slug' => 'network-card',
                'name' => 'Tarjeta de Red',
            ],
            [
                'type' => 'sink',
                'slug' => 'sink',
                'name' => 'Disipador',
            ],
            [
                'type' => 'fan',
                'slug' => 'fan',
                'name' => 'Ventilador',
            ],
            [
                'type' => 'liquid-refrigeration',
                'slug' => 'liquid-refrigeration',
                'name' => 'Refrigeración Líquida',
            ],
            [
                'type' => 'sound',
                'slug' => 'sound-card',
                'name' => 'Tarjeta de Sonido',
            ],
        ];

        $now = Carbon::now();

        foreach ($componentsAvailable as $ca) {
            $exist = DB::table('hardware_available_components')
                ->where('slug', $ca['slug'])
                ->first();

            if (! $exist) {
                DB::table('hardware_available_components')->insert([
                    'type' => $ca['type'],
                    'slug' => $ca['slug'],
                    'name' => $ca['name'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }
}
