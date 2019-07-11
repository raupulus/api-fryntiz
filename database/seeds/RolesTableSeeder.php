<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // id - name - display_name - created_at - updated_at
        DB::table('translations')->insert([
            'language_id' => 1,
            'token' => '1',
            'text' => 'Administrador',
        ]);

        DB::table('users_roles')->insert([
            'name' => 'admin',
            'translation_display_name_token' => 'Administrador',
        ]);

        DB::table('users_roles')->insert([
            'name' => 'user',
            'translation_display_name_token' => 'Usuario Normal',
        ]);
    }
}
