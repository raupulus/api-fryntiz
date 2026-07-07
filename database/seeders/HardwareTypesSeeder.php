<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class HardwareTypesSeeder
 *
 * Tipos de hardware por defecto (copiados de producción, con ids fijos).
 * Es idempotente: si un id ya existe no se reinserta, de modo que puede
 * ejecutarse varias veces sin duplicar ni fallar.
 */
class HardwareTypesSeeder extends Seeder
{
    private string $tableName = 'hardware_types';

    /**
     * @var array<int, array{id:int, name:string}>
     */
    private array $types = [
        ['id' => 1, 'name' => 'Monitor de Energía'],
        ['id' => 2, 'name' => 'Controlador Solar'],
        ['id' => 3, 'name' => 'PC Portátil'],
        ['id' => 4, 'name' => 'PC Desktop'],
        ['id' => 5, 'name' => 'Micro PC'],
        ['id' => 6, 'name' => 'Estación Meteorológica'],
        ['id' => 7, 'name' => 'Teléfono'],
        ['id' => 8, 'name' => 'Tablet'],
        ['id' => 9, 'name' => 'Coche'],
        ['id' => 10, 'name' => 'Impresora'],
        ['id' => 11, 'name' => 'Microcontrolador'],
    ];

    public function run(): void
    {
        $now = Carbon::now();

        foreach ($this->types as $type) {
            $exists = DB::table($this->tableName)->where('id', $type['id'])->exists();

            if (! $exists) {
                DB::table($this->tableName)->insert([
                    'id' => $type['id'],
                    'name' => $type['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Tras insertar ids explícitos, resincroniza la secuencia del serial
        // para que los siguientes autoincrementos no colisionen (PostgreSQL).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('{$this->tableName}', 'id'), ".
                "COALESCE((SELECT MAX(id) FROM {$this->tableName}), 1))"
            );
        }
    }
}
