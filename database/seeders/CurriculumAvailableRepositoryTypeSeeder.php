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
        $repositories = [
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

        foreach ($repositories as $repository) {
            $exist = DB::table('cv_available_repository_types')
                ->where('slug', $repository['slug'])
                ->first();

            if (! $exist) {
                DB::table('cv_available_repository_types')->insert([
                    'title' => $repository['title'],
                    'slug' => $repository['slug'],
                    'url' => $repository['url'],
                    'name' => $repository['name'],
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }
    }
}
