<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class PrinterAvailableTypesSeeder
 */
class ContentAvailableTypesSeeder extends Seeder
{
    private $tableName = 'content_available_types';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            [
                'name' => 'Página',
                'plural_name' => 'Páginas',
                'slug' => 'page',
                'description' => 'Páginas de la plataforma',
                'icon' => 'heroicon-o-document',
                'color' => '#3788d8',
            ],
            [
                'name' => 'Noticia',
                'plural_name' => 'Noticias',
                'slug' => 'new',
                'description' => 'Páginas de la plataforma',
                'icon' => 'heroicon-o-newspaper',
                'color' => '#39617b',
            ],
            [
                'name' => 'Blog',
                'plural_name' => 'Blogs',
                'slug' => 'blog',
                'description' => 'Blog',
                'icon' => 'heroicon-o-pencil-square',
                'color' => '#fbbf24',
            ],
            [
                'name' => 'Sección',
                'plural_name' => 'Secciones',
                'slug' => 'section',
                'description' => 'Secciones de la plataforma (about, contact...)',
                'icon' => 'heroicon-o-rectangle-group',
                'color' => '#bd0833',
            ],
            [
                'name' => 'Proyecto',
                'plural_name' => 'Proyectos',
                'slug' => 'project',
                'description' => 'Páginas de la plataforma',
                'icon' => 'heroicon-o-briefcase',
                'color' => '#aad3df',
            ],
            [
                'name' => 'Dispositivo',
                'plural_name' => 'Dispositivos',
                'slug' => 'device',
                'description' => 'Ficha con información del dispositivo',
                'icon' => 'heroicon-o-cpu-chip',
                'color' => '#1ab9a7',
            ],
            [
                'name' => 'Producto',
                'plural_name' => 'Productos',
                'slug' => 'product',
                'description' => 'Ficha con información de un producto',
                'icon' => 'heroicon-o-cube',
                'color' => '#8c76f6',
            ],
            [
                'name' => 'Publicidad',
                'plural_name' => 'Publicidades',
                'slug' => 'advertising',
                'description' => 'Publicidad',
                'icon' => 'heroicon-o-megaphone',
                'color' => '#e72a59',
            ],
            [
                'name' => 'Patrocinado',
                'plural_name' => 'Patrocinados',
                'slug' => 'sponsored',
                'description' => 'Publicidad patrocinada',
                'icon' => 'heroicon-o-star',
                'color' => '#fbbf24',
            ],
            [
                'name' => 'Documentación',
                'plural_name' => 'Documentaciones',
                'slug' => 'documentation',
                'description' => 'Documentos y/o manuales, documentación',
                'icon' => 'heroicon-o-book-open',
                'color' => '#2d5054',
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
