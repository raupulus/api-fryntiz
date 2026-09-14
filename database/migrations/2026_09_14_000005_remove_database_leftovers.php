<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quita lo que sobra en la base y no sale de ninguna migración.
 *
 * Comparando el esquema de producción (volcado del 2026-09-14) con el de una base
 * creada desde cero, sólo había dos cosas de más:
 *
 * 1. **La extensión `dblink`.** Se instaló a mano en producción y ningún código
 *    ni función de la base la usa.
 * 2. **Filas de `migrations` sin fichero.** Migraciones que se borraron del
 *    repositorio y siguen apuntadas como ejecutadas, p. ej.
 *    `2026_09_06_000003_add_energy_ability_to_existing_tokens`.
 *
 * Es el primer paso para dejar una migración por tabla: con esto la base del
 * servidor y la de una instalación nueva ya sólo difieren en lo que decidan las
 * migraciones.
 *
 * **La extensión sólo se quita si el usuario de la aplicación puede.** En
 * PostgreSQL sólo su dueño o un superusuario borra una extensión, y si la instaló
 * `postgres` un `DROP EXTENSION` fallaría y pararía el despliegue entero. En ese
 * caso se avisa por consola y se deja; hay que quitarla a mano con
 * `DROP EXTENSION IF EXISTS dblink;` como `postgres`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->quitaFilasSinFichero();
        $this->quitaDblink();
    }

    public function down(): void
    {
        // Sin vuelta atrás: ni la extensión ni esas filas pintan nada.
    }

    private function quitaFilasSinFichero(): void
    {
        $ficheros = array_map(
            static fn (string $ruta): string => basename($ruta, '.php'),
            glob(database_path('migrations/*.php')) ?: []
        );

        // Sin ficheros algo va muy mal (ruta equivocada): no se borra nada.
        if ($ficheros === []) {
            return;
        }

        DB::table('migrations')->whereNotIn('migration', $ficheros)->delete();
    }

    private function quitaDblink(): void
    {
        $extension = DB::selectOne(
            "SELECT pg_get_userbyid(e.extowner) = current_user
                    OR (SELECT rolsuper FROM pg_roles WHERE rolname = current_user) AS puede
             FROM pg_extension e WHERE e.extname = 'dblink'"
        );

        if ($extension === null) {
            return;
        }

        if (! $extension->puede) {
            fwrite(STDERR, "  ⚠ La extensión dblink sigue en la base: el usuario de la aplicación no puede borrarla. Quítala como postgres con: DROP EXTENSION IF EXISTS dblink;\n");

            return;
        }

        DB::statement('DROP EXTENSION IF EXISTS dblink');
    }
};
