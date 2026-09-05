<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Editores acotados por plataforma.
 *
 * Requisito: «algunos editores que puedan editar contenido para algunas de mis
 * webs pero no en otras». Hasta ahora el único eje de permisos era el rol, que
 * es global: o editas todo o no editas nada.
 *
 * Se añade el rol `editor` y una tabla pivote que dice en qué plataformas puede
 * trabajar cada usuario. Un admin o superadmin no necesita filas aquí: llega a
 * todas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_user', function (Blueprint $table) {
            $table->comment('Editores asignados a cada plataforma: un Editor sólo trabaja sobre las suyas.');
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('CASCADE')
                ->comment('Usuario con permiso de edición sobre la plataforma.');

            $table->foreignId('platform_id')
                ->constrained('platforms')
                ->onDelete('CASCADE')
                ->comment('Plataforma sobre la que puede trabajar el usuario.');

            $table->timestamps();

            $table->unique(['user_id', 'platform_id']);
            $table->index('platform_id');
        });

        // Rol de editor. Se inserta sólo si ya existen los roles base y no existe editor.
        if (DB::table('user_roles')->where('id', 1)->exists() && ! DB::table('user_roles')->where('name', 'editor')->exists()) {
            DB::table('user_roles')->insert([
                'name' => 'editor',
                'display_name' => 'Editor',
                'slug' => 'editor',
                'description' => 'Edita contenido sólo en las plataformas que tenga asignadas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_user');

        // Sólo se borra el rol si nadie lo tiene puesto.
        $role = DB::table('user_roles')->where('name', 'editor')->first();

        if ($role && ! DB::table('users')->where('role_id', $role->id)->exists()) {
            DB::table('user_roles')->where('id', $role->id)->delete();
        }
    }
};
