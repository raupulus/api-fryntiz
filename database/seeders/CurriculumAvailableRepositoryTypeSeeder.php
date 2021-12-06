<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class CurriculumAvailableRepositoryTypeSeeder
 */
class CurriculumAvailableRepositoryTypeSeeder extends Seeder
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
                'title' => 'Gitlab',
                'slug' => 'gitlab',
                'url' => 'https://gitlab.com',
                'name' => 'Gitlab',
            ],
            [
                'title' => 'Github',
                'slug' => 'github',
                'url' => 'https://github.com',
                'name' => 'Github',
            ],
            [
                'title' => 'Bitbucket',
                'slug' => 'bitbucket',
                'url' => 'https://bitbucket.org',
                'name' => 'Bitbucket',
            ],
        ];

        $now = Carbon::now();

        foreach ($componentsAvailable as $ca) {
            $exist = DB::table('cv_available_repository_types')
                ->where('slug', $ca['slug'])
                ->first();

            if (! $exist) {
                DB::table('cv_available_repository_types')->insert([
                    'title' => $ca['title'],
                    'slug' => $ca['slug'],
                    'url' => $ca['url'],
                    'name' => $ca['name'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }
}
