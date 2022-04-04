<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use function bcrypt;
use function config;

/**
 * Class UsersTableSeeder
 *
 * @package Database\Seeders
 */
class UsersTableSeeder extends Seeder
{
    private $tableName = 'users';

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
            'nickname' => 'superadmin',
            'email' => 'superadmin@domain.es',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);

        $t = $superadmin->createToken('general');

        if (config('app.debug')) {
            echo "\n\nToken Admin → $t->plainTextToken\n\n";
        }

        $admin = User::firstOrCreate([
            'email' => 'admin@domain.es',
        ],[
            'name' => 'Administrador',
            'role_id' => 2,
            'nickname' => 'admin',
            'email' => 'admin@domain.es',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);

        $user = User::firstOrCreate([
            'email' => 'user@domain.es',
            ],[
            'name' => 'Usuario Normal',
            'role_id' => 3,
            'nickname' => 'user',
            'email' => 'user@domain.es',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);
    }
}
