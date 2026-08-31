<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D98 — quita `deleted_at` de las tablas que **no** llevan borrado lógico.
 *
 * 44 tablas tenían la columna y sólo 4 modelos usaban el trait: la columna estaba
 * a `NULL` en todas las filas de todas ellas, porque ningún modelo la rellenaba
 * (**N190**). Una columna que nadie escribe y que hace creer que hay papelera es
 * peor que no tenerla.
 *
 * El criterio de D98:
 *
 * - **Assets** (`files`, `file_thumbnails`, `galleries`, `gallery_images`) →
 *   borrado real, y `unlink()` del fichero del disco. *"si se elimina un archivo
 *   deberá borrarse también del disco duro para ahorrar espacio"*.
 * - **Lecturas de sensores** → millones de filas; el borrado lógico penaliza
 *   todos los índices.
 * - **Catálogos** → pocas filas y se regeneran con el seeder.
 *
 * Antes de tirar cada columna se comprueba que esté vacía. Si alguna tuviera
 * datos, la migración se para en vez de perderlos.
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        // Assets: borrado real + fichero de disco.
        'files',
        'file_thumbnails',
        'galleries',
        'gallery_images',

        // Lecturas de sensores.
        'hardware_power_generators',

        // Catálogos: se regeneran con el seeder.
        'categories',
        'tags',
        'technologies',
        'platform_categories',
        'platform_tags',
        'content_available_status',
        'content_available_types',
        'hardware_types',
        'hardware_available_components',
        'social_networks',
        'user_roles',
        'file_types',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            $conDatos = DB::table($tableName)->whereNotNull('deleted_at')->count();

            if ($conDatos > 0) {
                throw new RuntimeException(sprintf(
                    '`%s` tiene %d fila(s) con `deleted_at` puesto. Se esperaba que la columna '.
                    'estuviera vacía (N190). Revisa esas filas antes de quitar la columna: '.
                    'o se restauran (`deleted_at = NULL`) o se borran de verdad.',
                    $tableName,
                    $conDatos
                ));
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'deleted_at')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
