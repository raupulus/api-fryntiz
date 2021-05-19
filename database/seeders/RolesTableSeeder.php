<?php
namespace Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class RolesTableSeeder
 *
 * @package Database\Seeders
 */
class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            ## Creo rol para administrador principal.
            DB::table('user_roles')->insert([
                'name' => 'superadmin',
                'display_name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Administrador Principal',
            ]);

            ## Creo rol para administradores.
            DB::table('user_roles')->insert([
                'name' => 'admin',
                'display_name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administradores',
            ]);

            ## Creo rol para usuario normal
            DB::table('user_roles')->insert([
                'name' => 'user',
                'display_name' => 'Usuario',
                'slug' => 'usuario',
                'description' => 'Usuario normal',
            ]);
        } catch (Exception $e) {
            Log::info('Ya existían los roles y traducciones');
        }
    }
}
