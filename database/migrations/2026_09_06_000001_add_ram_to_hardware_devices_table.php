<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Uso de memoria del dispositivo.
 *
 * Va con las que ya reporta el propio cacharro —`cpu`, `disk`, `temp`,
 * `battery_level`— porque es igual de común de medir en IoT y no había dónde
 * guardarlo: hasta ahora acababa en `extra`, que es JSON y no se puede ordenar
 * ni graficar.
 *
 * Columna nullable y sin valor por defecto: en PostgreSQL eso es instantáneo y
 * no reescribe la tabla, así que se puede aplicar con la API en marcha y las
 * lecturas entrando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table) {
            $table->decimal('ram', 5, 2)
                ->nullable()
                ->after('disk')
                ->comment('Último uso de memoria conocido en porcentaje (0-100).');
        });
    }

    public function down(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table) {
            $table->dropColumn('ram');
        });
    }
};
