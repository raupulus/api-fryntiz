<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddWtcAndAircraftDescToAirFlightAirplanesTable
 *
 * Datos estáticos de célula/modelo, resueltos por el receptor contra la
 * misma base local que registration/aircraft_type — ver
 * docs/future/airflight-registro-de-matriculas.md.
 */
class AddWtcAndAircraftDescToAirFlightAirplanesTable extends Migration
{
    private $tableName = 'airflight_airplanes';

    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('wtc', 1)
                ->nullable()
                ->after('aircraft_type')
                ->comment('Wake Turbulence Category OACI: L (Light), M (Medium), H (Heavy), J (Super). Resuelto por el receptor contra su base local; null si no está en esa base.');

            $table->string('aircraft_desc', 5)
                ->nullable()
                ->after('wtc')
                ->comment('Descripción OACI de fuselaje/propulsión (ej: L2J = avión, 2 motores, reactor). Resuelto por el receptor contra su base local; null si no está en esa base.');
        });
    }

    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['wtc', 'aircraft_desc']);
        });
    }
}
