<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La batería del propio dispositivo va en el dispositivo (D108).
 *
 * **No es una lectura de energía.** Meterla en las tablas de energía es
 * exactamente lo que hacía el caso especial del dispositivo 14: una Pico
 * mandando su tensión de batería acababa como si fuera una medida de consumo, y
 * contaminaba los agregados.
 *
 * La puede mandar cualquier endpoint IoT —estación, keycounter, smartplant,
 * energía— y siempre es opcional.
 *
 * `hardware_devices` ya tenía `voltage` y `battery_level` del estado genérico;
 * estas tres columnas son las del contrato de energía (D115), con su marca de
 * tiempo propia para saber si el dato es de ahora o de hace tres semanas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('hardware_devices', 'battery_voltage')) {
                $table->decimal('battery_voltage', 8, 3)->nullable()
                    ->comment('Tensión de la batería del propio dispositivo (V).');
            }

            if (! Schema::hasColumn('hardware_devices', 'battery_percentage')) {
                $table->unsignedTinyInteger('battery_percentage')->nullable()
                    ->comment('Carga de la batería del propio dispositivo (%).');
            }

            $table->timestamp('battery_read_at')->nullable()
                ->comment('Cuándo se midió. Sin esto no se distingue un dato de ahora de uno de hace semanas.');
        });
    }

    public function down(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table) {
            $table->dropColumn(['battery_voltage', 'battery_percentage', 'battery_read_at']);
        });
    }
};
