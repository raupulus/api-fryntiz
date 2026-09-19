<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Class NormalizeEmptyCategoryInAirFlightAirplanes
 *
 * "Categoría desconocida" se guardaba de dos formas: `''` en los aviones
 * anteriores al 2026-09-08 (arrastre de V1) y `NULL` en los posteriores.
 * `AirFlightService::addAircraft()` ya ignora los valores vacíos, así que
 * `NULL` es la única forma que se sigue produciendo.
 */
class NormalizeEmptyCategoryInAirFlightAirplanes extends Migration
{
    public function up()
    {
        DB::table('airflight_airplanes')->where('category', '')->update(['category' => null]);
    }

    public function down()
    {
        // No hay marcha atrás: no se sabe qué filas tenían '' y cuáles NULL.
    }
}
