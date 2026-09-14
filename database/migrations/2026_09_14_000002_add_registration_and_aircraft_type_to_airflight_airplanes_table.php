<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddRegistrationAndAircraftTypeToAirFlightAirplanesTable
 *
 * Matrícula y tipo de aeronave, resueltos por el propio receptor ADS-B contra
 * la base de matrículas que ya trae instalada (dump1090-fa/skyaware), no por
 * esta API. Documentado en docs/future/airflight-registro-de-matriculas.md.
 */
class AddRegistrationAndAircraftTypeToAirFlightAirplanesTable extends Migration
{
    private $tableName = 'airflight_airplanes';

    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('registration')
                ->nullable()
                ->after('icao')
                ->comment('Matrícula de la aeronave (ej: EC-NBA), resuelta por el receptor contra su base local; null si no está en esa base.');

            $table->string('aircraft_type', 10)
                ->nullable()
                ->after('registration')
                ->comment('Tipo ICAO de aeronave (ej: A320, BE20), resuelto por el receptor contra su base local; null si no está en esa base.');
        });
    }

    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['registration', 'aircraft_type']);
        });
    }
}
