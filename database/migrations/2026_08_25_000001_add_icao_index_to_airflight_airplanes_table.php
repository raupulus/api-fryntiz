<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `airflight_airplanes` no tenía **ni un índice** (**N292**).
 *
 * Es la tabla que se consulta por `icao` en cada sondeo del receptor ADS-B —
 * varias veces por segundo cuando hay tráfico— y cada consulta era un seq scan
 * sobre toda la tabla.
 *
 * El índice no es único a propósito: la tabla puede traer duplicados de antes,
 * de cuando `addAircraft()` hacía `create()` a ciegas. La deduplicación y el
 * índice único van juntos, y eso es una migración de datos aparte.
 */
return new class extends Migration
{
    private string $tableName = 'airflight_airplanes';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->index('icao', 'airflight_airplanes_icao_idx');
            $table->index('seen_last_at', 'airflight_airplanes_seen_last_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropIndex('airflight_airplanes_icao_idx');
            $table->dropIndex('airflight_airplanes_seen_last_at_idx');
        });
    }
};
