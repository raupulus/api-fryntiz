<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use function array_merge;

/**
 * Class CurriculumAvailableRepositoryTypeSeeder
 */
class CurriculumAvailableRepositoryTypeSeeder extends Seeder
{
    private $tableName = 'cv_available_repository_types';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
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
