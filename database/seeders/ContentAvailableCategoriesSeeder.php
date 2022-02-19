<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class ContentAvailableCategoriesSeeder
 */
class ContentAvailableCategoriesSeeder extends Seeder
{
    private $tableName = 'content_available_categories';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [

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
