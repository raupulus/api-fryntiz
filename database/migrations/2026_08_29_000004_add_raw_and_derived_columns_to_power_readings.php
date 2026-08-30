<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las lecturas de energía: crudos, derivados y trazabilidad.
 *
 * Tres cosas que faltaban:
 *
 *  1. **De qué elemento es la lectura.** Sólo se sabía de qué dispositivo, y un
 *     monitor mide varias cosas a la vez.
 *  2. **Los crudos.** Se guardaba `power` (instantánea) y nada más. `SUM(power)`
 *     no son vatios-hora: da un número que depende de cuántas veces midió el
 *     sensor. Con `amperage`, `voltage` y `delta_seconds` guardados, si un día
 *     se descubre que un elemento tenía la tensión mal puesta se recalcula el
 *     histórico entero.
 *  3. **De dónde salió cada número.** Un Wh que da el aparato y uno derivado no
 *     tienen la misma precisión, y mezclarlos sin saberlo estropea las sumas.
 *
 * Una lectura rara se marca, nunca se descarta (D72).
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private const TABLES = [
        'hardware_power_loads',
        'hardware_power_generators',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'hardware_energy_id')) {
                    $table->foreignId('hardware_energy_id')
                        ->nullable()->after('hardware_device_id')
                        ->constrained('hardware_energy')->nullOnDelete()
                        ->comment('Elemento concreto al que corresponde la lectura.');
                }

                // La tabla de generadores no tenía dónde guardar la temperatura
                // del aparato —sólo la de la batería—, y sin embargo su resumen
                // del día declara `temperature_min` y `temperature_max`: dos
                // columnas que no se han rellenado nunca porque el dato no
                // llegaba a ninguna parte.
                if ($tableName === 'hardware_power_generators' && ! Schema::hasColumn($tableName, 'temperature')) {
                    $table->decimal('temperature', 6, 3)->nullable()
                        ->comment('Temperatura del aparato (°C). La de la batería es `battery_temperature`.');
                }

                // Crudos. La verdad; no se tiran nunca.
                $table->unsignedInteger('delta_seconds')->nullable()
                    ->comment('Segundos que cubre la media. Sin esto, A y V no dan energía.');

                // Derivados: caché para no recalcular millones de filas.
                $table->decimal('energy_wh', 14, 4)->nullable()
                    ->comment('V*A*s/3600. Esto SÍ se suma entre lecturas.');
                $table->decimal('energy_ah', 14, 4)->nullable()
                    ->comment('A*s/3600. Esto SÍ se suma entre lecturas.');

                // De dónde salió cada número.
                $table->string('energy_source', 16)->default('derived')
                    ->comment('device = lo dio el aparato | derived = lo calculamos.');
                $table->string('voltage_source', 16)->default('measured')
                    ->comment('measured = tensión medida | nominal = la del elemento.');

                // Una lectura rara se marca, no se descarta (D72).
                $table->boolean('is_suspicious')->default(false)
                    ->comment('Queda fuera de los agregados del día, pero se conserva.');
                $table->string('suspicious_reason', 255)->nullable();

                $table->index(['hardware_energy_id', 'read_at']);
                $table->index(['hardware_device_id', 'read_at']);
                $table->index(['is_suspicious', 'read_at']);
            });

            // La precisión de los crudos, normalizada al contrato (D115).
            //
            // Un nodo IoT consume entre 100 y 500 mA. En `hardware_power_generators`
            // la corriente era `decimal(10,2)`: 0,124 A se guardaban como 0,12 y ahí
            // se va un 3 % del dato del que sale todo lo demás. En
            // `hardware_power_loads` era `double`, que para dinero o para sumar
            // millones de filas tampoco vale.
            //
            // Y se caen los `default(0)` de los generadores: «no tengo dato» no es
            // cero, y un cero por defecto se cuela en las medias como si fuera una
            // medición real.
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('amperage', 10, 3)->nullable()
                    ->comment('Corriente MEDIA del periodo (A). Crudo: no se recalcula ni se tira.')
                    ->change();
                $table->decimal('voltage', 10, 3)->nullable()
                    ->comment('Tensión del periodo (V). Crudo.')
                    ->change();
                $table->decimal('power', 12, 3)->nullable()
                    ->comment('V*A. Potencia MEDIA del periodo, no instantánea.')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            // Cada tabla vuelve al tipo que tenía: no eran iguales.
            if ($tableName === 'hardware_power_loads') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->double('amperage')->nullable()->change();
                    $table->decimal('voltage', 8, 3)->nullable()->change();
                    $table->double('power')->nullable()->change();
                });
            } else {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->decimal('amperage', 10, 2)->nullable()->default(0)->change();
                    $table->decimal('voltage', 10, 2)->nullable()->default(0)->change();
                    $table->decimal('power', 10, 2)->nullable()->default(0)->change();
                });
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['hardware_energy_id', 'read_at']);
                $table->dropIndex(['hardware_device_id', 'read_at']);
                $table->dropIndex(['is_suspicious', 'read_at']);
                $table->dropConstrainedForeignId('hardware_energy_id');
                $table->dropColumn([
                    'delta_seconds', 'energy_wh', 'energy_ah',
                    'energy_source', 'voltage_source',
                    'is_suspicious', 'suspicious_reason',
                ]);

                if ($tableName === 'hardware_power_generators') {
                    $table->dropColumn('temperature');
                }
            });
        }
    }
};
