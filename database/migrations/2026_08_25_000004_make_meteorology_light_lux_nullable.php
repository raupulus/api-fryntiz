<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `meteorology_light.lux` pasa a NULLABLE — lo dicen los datos de producción.
 *
 * La migración original la creó NOT NULL, pero en producción hay **160.306 filas
 * con `lux` a NULL** de 2.619.179 (el 6 %). O sea que la columna real nunca fue
 * NOT NULL, y el sensor no siempre da ese valor.
 *
 * `lumens`, en cambio, está en **el 100 %** de las filas desde 2019: ésa sí es
 * obligatoria de verdad y se queda como está (**N286**, **D114**).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('meteorology_light', 'lux')) {
            return;
        }

        // `->change()` necesita doctrine/dbal en algunas versiones; en PostgreSQL
        // el ALTER directo es más fiable y no añade dependencia.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE meteorology_light ALTER COLUMN lux DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Volver a NOT NULL exige que no queden nulos.
        DB::table('meteorology_light')->whereNull('lux')->update(['lux' => 0]);
        DB::statement('ALTER TABLE meteorology_light ALTER COLUMN lux SET NOT NULL');
    }
};
