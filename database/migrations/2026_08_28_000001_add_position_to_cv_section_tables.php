<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orden manual en las secciones del currículum (B4).
 *
 * Las tablas de experiencia ya tenían `position`; las otras siete no, así que
 * salían en el orden en que la base de datos las devolviera. Un CV donde no
 * puedes decidir en qué orden aparecen tus trabajos no sirve: el orden **es**
 * la información.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = [
        'cv_jobs',
        'cv_projects',
        'cv_repositories',
        'cv_services',
        'cv_collaborations',
        'cv_hobbies',
        'cv_skills',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'position')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('position')
                    ->default(0)
                    ->comment('Orden manual dentro de su sección del currículum.');
            });

            // Numera lo que ya hubiera, por currículum, respetando el orden
            // actual: así el primer guardado no lo baraja todo.
            $this->numberExisting($tableName);

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->index(['curriculum_id', 'position'], $tableName.'_curriculum_position_index');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'position')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName.'_curriculum_position_index');
                $table->dropColumn('position');
            });
        }
    }

    private function numberExisting(string $tableName): void
    {
        DB::statement("
            UPDATE {$tableName} AS t
            SET position = numerado.fila
            FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY curriculum_id ORDER BY id) AS fila
                FROM {$tableName}
            ) AS numerado
            WHERE t.id = numerado.id
        ");
    }
};
