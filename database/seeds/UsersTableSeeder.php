<?php

use App\User;
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
        $admin = User::firstOrCreate([
            'name' => 'Administrador Principal',
            'role_id' => 1,
            'email' => 'admin@domain.es',
            'password' => bcrypt('temp'),
        ]);

        $user = User::firstOrCreate([
            'name' => 'Usuario Normal',
            'role_id' => 2,
            'email' => 'user@domain.es',
            'password' => bcrypt('temp'),
        ]);
    }
}
