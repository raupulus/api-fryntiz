<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use function bcrypt;

/**
 * Class UsersTableSeeder
 *
 * @package Database\Seeders
 */
class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $superadmin = User::firstOrCreate([
                'email' => 'superadmin@domain.es',
            ],[
            'name' => 'Administrador Principal',
            'role_id' => 1,
            'email' => 'superadmin@domain.es',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);

        $admin = User::firstOrCreate([
            'email' => 'admin@domain.es',
        ],[
            'name' => 'Administrador',
            'role_id' => 2,
            'email' => 'admin@domain.es',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);

        $user = User::firstOrCreate([
            'email' => 'user@domain.es',
            ],[
            'name' => 'Usuario Normal',
            'role_id' => 3,
            'email' => 'user@domain.es',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);
    }
}
