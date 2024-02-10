<?php

namespace Database\Seeders;

use App\Models\Technology;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class ContentAvailableCategoriesSeeder
 */
class TechnologiesSeeder extends Seeder
{
    private $tableName = 'technologies';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 'name', 'slug', 'description', 'color'
        $datas = [
            [
                'name' => 'PHP',
                'slug' => 'php',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'VueJs',
                'slug' => 'vuejs',
                'description' => '',
                'color' => '#00ff00',
            ],
            [
                'name' => 'nuxt',
                'slug' => 'Nuxt',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Javascript',
                'slug' => 'javascript',
                'description' => '',
                'color' => '#f0f000',
            ],
            [
                'name' => 'PostgreSQL',
                'slug' => 'postresql',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Angular',
                'slug' => 'angular',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'NodeJs',
                'slug' => 'nodejs',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'MySql',
                'slug' => 'mysql',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Redis',
                'slug' => 'redis',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Sqlite',
                'slug' => 'sqlite',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Python',
                'slug' => 'python',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'C',
                'slug' => 'c',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'C++',
                'slug' => 'c_plus_plus',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Ionic Vue',
                'slug' => 'ionic-vue',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Ionic Angular',
                'slug' => 'ionic-angular',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Bash',
                'slug' => 'bash',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'PlSQL',
                'slug' => 'plsql',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'jQuery',
                'slug' => 'jquery',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Bootstrap',
                'slug' => 'bootstrap',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Tailwind',
                'slug' => 'tailwind',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'CSS',
                'slug' => 'css',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Java',
                'slug' => 'java',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'HTML',
                'slug' => 'html',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'GNU/Linux',
                'slug' => 'gnu-linux',
                'description' => '',
                'color' => '#ff0000',
            ],
            [
                'name' => 'Macos',
                'slug' => 'macos',
                'description' => '',
                'color' => '#ff0000',
            ],
        ];

        //$now = Carbon::now();

        foreach ($datas as $data) {
            Technology::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
