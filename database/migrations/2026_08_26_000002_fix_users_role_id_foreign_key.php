<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La clave foránea `users.role_id` declaraba `onDelete('SET NULL')` sobre una
 * columna `NOT NULL` (auditoría A12). Es contradictorio: borrar un rol que
 * tenga usuarios falla igualmente, pero con un error de restricción ilegible
 * en vez de con el error correcto.
 *
 * Se cambia a RESTRICT, que es lo que de hecho pasaba.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->foreign('role_id')
                ->references('id')
                ->on('user_roles')
                ->onDelete('RESTRICT')
                ->onUpdate('CASCADE');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->foreign('role_id')
                ->references('id')
                ->on('user_roles')
                ->onDelete('SET NULL');
        });
    }
};
