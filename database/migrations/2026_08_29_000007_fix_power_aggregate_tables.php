<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los resúmenes del día y el acumulado, arreglados (D115, punto 3 del diseño).
 *
 * Las cuatro tablas de agregados arrastraban los dos mismos fallos que las de
 * lecturas, y por eso los totales que devuelve la API no significan nada:
 *
 *  1. **Agregaban por dispositivo, no por elemento.** Un monitor mide un panel
 *     y un router a la vez; sus dos corrientes acababan sumadas en la misma
 *     fila. Es el mismo agujero que abre `hardware_energy_id` en las lecturas.
 *  2. **`power` y `amperage` eran acumuladores de potencia instantánea.**
 *     `SUM(power)` no son vatios-hora: da un número que sube si el sensor mide
 *     más veces y baja si mide menos. No hay forma de arreglar esas dos
 *     columnas manteniendo el nombre, porque el nombre es el que está mal, así
 *     que se van y entran `energy_wh` y `energy_ah`, que sí se suman.
 *
 * Los mínimos y máximos (`power_min`, `power_max`, `amperage_min`,
 * `amperage_max`) se quedan: esos sí son instantáneos y ahí tienen sentido.
 *
 * Lo que había en `power` y `amperage` no se pierde por el camino porque no
 * había nada que perder: `recalculateToday()` buscaba la última fila del
 * dispositivo **sin filtrar por fecha**, así que reescribía siempre la misma y
 * movía su `date` a hoy. Nunca hubo más de una fila por dispositivo, con lo que
 * el `count(id) as days_operating` del histórico valía 1 desde 2022. Todo eso
 * se recalcula desde las lecturas crudas, que son las que sí están bien.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = [
        'hardware_power_loads_today',
        'hardware_power_loads_historical',
        'hardware_power_generators_today',
        'hardware_power_generators_historical',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('hardware_energy_id')
                    ->nullable()->after('hardware_device_id')
                    ->constrained('hardware_energy')->nullOnDelete()
                    ->comment('Elemento al que corresponde el resumen. Sin esto se suman el panel y el router.');

                $table->decimal('energy_wh', 16, 4)->nullable()
                    ->comment('Vatios-hora del periodo. Esto SÍ se suma.');
                $table->decimal('energy_ah', 14, 4)->nullable()
                    ->comment('Amperios-hora del periodo. Esto SÍ se suma.');

                $table->unsignedInteger('readings_count')->default(0)
                    ->comment('Lecturas no sospechosas que entran en el resumen.');

                foreach (['amperage', 'power'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }

                // El resumen del día de los generadores tenía el máximo pero no
                // el mínimo, y el de consumos los dos. Misma tabla conceptual,
                // misma forma: la corriente mínima de carga de un día es un dato
                // (de noche es 0) y se estaba tirando.
                if ($tableName === 'hardware_power_generators_today') {
                    $table->decimal('amperage_min', 10, 3)->nullable()
                        ->comment('Corriente mínima instantánea del día (A).');
                    $table->decimal('power_min', 12, 3)->nullable()
                        ->comment('Potencia mínima instantánea del día (W).');
                }
            });

            // El índice por el que se busca ahora: elemento + día.
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'date')) {
                    $table->index(['hardware_energy_id', 'date'], substr($tableName, 0, 40).'_energy_date_idx');
                } else {
                    $table->index('hardware_energy_id', substr($tableName, 0, 40).'_energy_idx');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex(
                    Schema::hasColumn($tableName, 'date')
                        ? substr($tableName, 0, 40).'_energy_date_idx'
                        : substr($tableName, 0, 40).'_energy_idx'
                );

                $table->dropConstrainedForeignId('hardware_energy_id');
                $table->dropColumn(['energy_wh', 'energy_ah', 'readings_count']);

                if ($tableName === 'hardware_power_generators_today') {
                    $table->dropColumn(['amperage_min', 'power_min']);
                }

                if (str_ends_with($tableName, '_historical')) {
                    $table->decimal('amperage', 20, 6)->nullable();
                    $table->decimal('power', 20, 6)->nullable();
                } else {
                    $table->double('amperage')->nullable();
                    $table->double('power')->nullable();
                }
            });
        }
    }
};
