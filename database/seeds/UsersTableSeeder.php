<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Administrador Principal',
            'role_id' => 1,
            'email' => 'admin@domain.es',
            'password' => bcrypt('temp'),
        ]);

        DB::table('users')->insert([
            'name' => 'Usuario Normal',
            'role_id' => 2,
            'email' => 'user@domain.es',
            'password' => bcrypt('temp'),
        ]);
    }
}
