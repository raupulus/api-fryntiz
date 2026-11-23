<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

use function bcrypt;
use function config;

/**
 * Class UsersTableSeeder
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
            'email' => 'superadmin@example.test',
        ], [
            'name' => 'Administrador Principal',
            'role_id' => 1,
            'nickname' => 'superadmin',
            'email' => 'superadmin@example.test',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);

        $t = $superadmin->createToken('general');

        if (config('app.debug')) {
            echo "\n\nToken Admin → $t->plainTextToken\n\n";
        }

        $admin = User::firstOrCreate([
            'email' => 'admin@example.test',
        ], [
            'name' => 'Administrador',
            'role_id' => 2,
            'nickname' => 'admin',
            'email' => 'admin@example.test',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);

        $user = User::firstOrCreate([
            'email' => 'user@example.test',
        ], [
            'name' => 'Usuario Normal',
            'role_id' => 3,
            'nickname' => 'user',
            'email' => 'user@example.test',
            'email_verified_at' => '2021-03-03 12:00:00',
            'password' => bcrypt('123123'),
        ]);
    }
}
