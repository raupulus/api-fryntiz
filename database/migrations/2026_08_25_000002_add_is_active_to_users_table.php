<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * N192 — «desactivado» y «borrado» son dos estados distintos y necesitan dos
 * columnas.
 *
 * `User::toggleActive()` usaba `deleted_at` como bandera de desactivado. Con el
 * trait `SoftDeletes` activo (D98) eso significa que desactivar a alguien lo hace
 * **desaparecer de todos los listados, incluido aquel desde el que se le acaba de
 * desactivar**. Y no hay forma de volver a activarlo desde la interfaz.
 *
 * Los usuarios que hoy tengan `deleted_at` puesto están *desactivados*, no
 * borrados: se migran a `is_active = false` y se les limpia `deleted_at`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')
                ->default(true)
                ->after('role_id')
                ->comment('Indica si la cuenta está activa. Distinto de deleted_at, que es borrado lógico.');
        });

        // Lo que hasta ahora significaba "desactivado" pasa a su columna.
        DB::table('users')->whereNotNull('deleted_at')->update([
            'is_active' => false,
            'deleted_at' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('is_active', false)->update([
            'deleted_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
