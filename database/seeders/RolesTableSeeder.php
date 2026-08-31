<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class RolesTableSeeder
 */
class RolesTableSeeder extends Seeder
{
    private $tableName = 'user_roles';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Administrador Principal'],
            ['id' => 2, 'name' => 'admin', 'display_name' => 'Admin', 'slug' => 'admin', 'description' => 'Administradores'],
            ['id' => 3, 'name' => 'user', 'display_name' => 'Usuario', 'slug' => 'usuario', 'description' => 'Usuario normal'],
            ['id' => 4, 'name' => 'editor', 'display_name' => 'Editor', 'slug' => 'editor', 'description' => 'Edita contenido sólo en las plataformas que tenga asignadas'],
        ];

        foreach ($roles as $role) {
            $existing = DB::table('user_roles')->where('name', $role['name'])->first();
            if ($existing) {
                DB::table('user_roles')->where('id', $existing->id)->update([
                    'display_name' => $role['display_name'],
                    'slug' => $role['slug'],
                    'description' => $role['description'],
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('user_roles')->updateOrInsert(
                    ['id' => $role['id']],
                    array_merge($role, ['created_at' => now(), 'updated_at' => now()])
                );
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('user_roles', 'id'), coalesce(max(id), 1)) FROM user_roles;");
        }
    }
}
