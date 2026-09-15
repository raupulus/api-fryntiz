<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddNicAndRcToAirFlightRoutesTable
 *
 * NIC/RC son la métrica de fiabilidad de la propia posición (lat/lon) de
 * esta misma fila, no telemetría nueva sin relación con lo que ya se
 * guarda — ver docs/future/airflight-registro-de-matriculas.md.
 */
class AddNicAndRcToAirFlightRoutesTable extends Migration
{
    private $tableName = 'airflight_routes';

    public function up()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->unsignedTinyInteger('nic')
                ->nullable()
                ->after('rssi')
                ->comment('Navigation Integrity Category (0-11): fiabilidad del enlace GPS/ADS-B que produjo esta posición.');

            $table->float('rc')
                ->nullable()
                ->after('nic')
                ->comment('Radius of Containment en metros: radio de contención de la posición reportada en esta fila.');
        });
    }

    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['nic', 'rc']);
        });
    }
}
