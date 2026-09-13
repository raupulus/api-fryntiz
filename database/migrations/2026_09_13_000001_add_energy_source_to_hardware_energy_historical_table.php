<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * De dónde sale cada acumulado de una sesión histórica, **magnitud a magnitud**.
 *
 * `energy_wh` y `energy_ah` de `hardware_energy_historical` eran dos cosas a la
 * vez: un odómetro absoluto en las lecturas que traían `historical_*` y un
 * acumulador incremental en las que no. Bastaba con que un aparato con odómetro
 * se dejara el campo en una lectura para que se le sumara un delta encima de su
 * total, y como el valor se guarda con `max()`, ese inflado no se deshacía
 * nunca.
 *
 * **Son dos columnas y no una porque un aparato puede traer odómetro de una
 * magnitud y no de la otra.** El Renogy Rover manda los Wh y los Ah de
 * generación y de consumo, pero de la batería sólo manda amperios-hora: no hay
 * registro Modbus de vatios-hora de batería. Con una sola columna, marcar la
 * sesión como `device` congelaba también la magnitud que el aparato no manda y
 * la dejaba clavada a 0 para siempre. Con una por magnitud se cumple la regla
 * que toca: **lo que el aparato manda se guarda tal cual; lo que no manda se
 * calcula de las lecturas.**
 *
 * `auto_calculate_history` de `hardware_energy` declara la intención, pero es
 * una casilla que se puede quedar mal puesta —de hecho su `default(true)` marcó
 * como auto-calculados a los controladores que traen odómetro propio—. Esto es
 * un hecho observado de la serie: la primera lectura con odómetro de esa
 * magnitud fija su origen en `device` y a partir de ahí sus deltas se ignoran.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy_historical';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('energy_wh_source', 16)
                ->default('derived')
                ->after('readings_count')
                ->comment('device = los vatios-hora los lleva el odómetro del aparato | derived = los sumamos de nuestras lecturas.');

            $table->string('energy_ah_source', 16)
                ->default('derived')
                ->after('energy_wh_source')
                ->comment('device = los amperios-hora los lleva el odómetro del aparato | derived = los sumamos de nuestras lecturas.');
        });

        // Saneamiento del controlador solar Renogy Rover (#6).
        //
        // Qué manda de verdad cada elemento, según el contrato de la V1 que
        // llevaba años funcionando (`StoreSolarChargeRequest` de la rama `main`)
        // y los registros Modbus que hay en `hardware_power_generators_solar`:
        //
        // - Generador #4: `historical_cumulative_power_generation` (Wh) y
        //   `historical_total_charging_amp_hours` (Ah). Odómetro en las dos.
        // - Consumo #7: `historical_cumulative_power_consumption` (Wh) y
        //   `historical_total_discharging_amp_hours` (Ah). Odómetro en las dos.
        // - Batería #11: el Rover no tiene registro de vatios-hora de batería,
        //   así que sus Wh se calculan de las lecturas y sólo los Ah son suyos.
        DB::table($this->tableName)
            ->where('hardware_device_id', 6)
            ->whereIn('hardware_energy_id', [4, 7])
            ->update(['energy_wh_source' => 'device', 'energy_ah_source' => 'device']);

        DB::table($this->tableName)
            ->where('hardware_device_id', 6)
            ->where('hardware_energy_id', 11)
            ->update(['energy_ah_source' => 'device']);
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['energy_wh_source', 'energy_ah_source']);
        });
    }
};
