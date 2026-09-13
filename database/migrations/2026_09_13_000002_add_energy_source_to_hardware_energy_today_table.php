<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De dónde sale cada acumulado **del día**, magnitud a magnitud.
 *
 * Mismo problema que en `hardware_energy_historical` y por el mismo motivo, pero
 * en la tabla del día pasaba algo peor: el cierre nocturno hacía
 *
 * ```php
 * $finalWh = max($todayRecord->energy_wh, $agg->sum_wh);
 * ```
 *
 * y con eso **sustituía el total que había declarado el aparato por la suma de
 * nuestras lecturas** en cuanto la nuestra salía mayor. Un controlador que
 * declaraba 800 Wh del día amanecía con 1.500 porque nuestras lecturas, todas
 * integradas con el intervalo por defecto, sumaban más.
 *
 * Con estas dos columnas la tabla del día sigue la misma regla que el resto del
 * módulo: lo que el aparato declara se respeta y lo que no declara se calcula.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy_today';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('energy_wh_source', 16)
                ->default('derived')
                ->after('readings_count')
                ->comment('device = los vatios-hora del día los declara el aparato | derived = los sumamos de nuestras lecturas.');

            $table->string('energy_ah_source', 16)
                ->default('derived')
                ->after('energy_wh_source')
                ->comment('device = los amperios-hora del día los declara el aparato | derived = los sumamos de nuestras lecturas.');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['energy_wh_source', 'energy_ah_source']);
        });
    }
};
