<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `2026_09_14_000005_remove_database_leftovers`: lo que sobra en la base y no sale
 * de ninguna migración.
 *
 * La extensión `dblink` no se prueba aquí: crearla necesita permisos que la base
 * de pruebas no tiene por qué dar. Se comprobó a mano contra una copia de
 * producción.
 */
class RemoveDatabaseLeftoversMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function ejecutaLaMigracion(): void
    {
        (require database_path('migrations/2026_09_14_000005_remove_database_leftovers.php'))->up();
    }

    #[Test]
    public function rows_without_a_migration_file_are_removed(): void
    {
        DB::table('migrations')->insert([
            ['migration' => '2026_09_06_000003_add_energy_ability_to_existing_tokens', 'batch' => 5],
            ['migration' => '2022_01_30_232819_create_hardware_power_loads_table', 'batch' => 1],
        ]);

        $this->ejecutaLaMigracion();

        $this->assertFalse(DB::table('migrations')->where('migration', '2026_09_06_000003_add_energy_ability_to_existing_tokens')->exists());
        $this->assertFalse(DB::table('migrations')->where('migration', '2022_01_30_232819_create_hardware_power_loads_table')->exists());
    }

    #[Test]
    public function every_row_with_a_file_is_kept(): void
    {
        $antes = DB::table('migrations')->count();
        $ficheros = count(glob(database_path('migrations/*.php')) ?: []);

        $this->ejecutaLaMigracion();

        $this->assertSame($antes, DB::table('migrations')->count());
        $this->assertSame($ficheros, $antes, 'Tras migrar, cada fichero tiene su fila y no hay más');
    }

    #[Test]
    public function running_it_twice_changes_nothing(): void
    {
        $this->ejecutaLaMigracion();
        $primera = DB::table('migrations')->orderBy('id')->pluck('migration')->all();

        $this->ejecutaLaMigracion();

        $this->assertSame($primera, DB::table('migrations')->orderBy('id')->pluck('migration')->all());
    }
}
