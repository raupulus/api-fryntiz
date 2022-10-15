<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class ContentAvailableStatusSeeder
 */
class ContentAvailableStatusSeeder extends Seeder
{
    private $tableName = 'content_available_status';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            [
                'name' => 'Borrador',
                'slug' => 'draft',
                'description' => 'Borrador para contenido incompleto.',
                'icon' => 'fa-solid fa-compass-drafting',
                'color' => '#ffa753',
            ],
            [
                'name' => 'Programado',
                'slug' => 'programmed',
                'description' => 'Programado para publicarse en la fecha especificada.',
                'icon' => 'fa-solid fa-timer',
                'color' => '#89ce40',
            ],
            [
                'name' => 'Publicado',
                'slug' => 'published',
                'description' => 'Contenido publicado correctamente.',
                'icon' => 'fa-solid fa-eye',
                'color' => '#39617b',
            ],
            [
                'name' => 'No Publicado',
                'slug' => 'not-published',
                'description' => 'Contenido listo pero sin publicar.',
                'icon' => 'fa-solid fa-eye-slash',
                'color' => '#e72a59',
            ],
            [
                'name' => 'Copyright Protected',
                'slug' => 'copyright-protected',
                'description' => 'Contenido marcado por incumplir el copyright.',
                'icon' => 'fa-solid fa-copyright',
                'color' => '#00ff00',
            ],
            [
                'name' => 'Para eliminar',
                'slug' => 'to-remove',
                'description' => 'Contenido marcado para eliminar.',
                'icon' => 'fa-solid fa-trash',
                'color' => '#ff0000',
            ],

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
