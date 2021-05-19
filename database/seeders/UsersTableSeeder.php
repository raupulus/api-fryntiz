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
                'email' => 'admin@domain.es',
            ],[
            'name' => 'Administrador Principal',
            'role_id' => 1,
            'email' => 'admin@domain.es',
            'password' => bcrypt('temp'),
        ]);

        $user = User::firstOrCreate([
            'email' => 'user@domain.es',
            ],[
            'name' => 'Usuario Normal',
            'role_id' => 2,
            'email' => 'user@domain.es',
            'password' => bcrypt('temp'),
        ]);
    }
}
