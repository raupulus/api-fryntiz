<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acumulados de energía de por vida, por sesión.
 *
 * Si el odómetro del aparato se reinicia, se abre una sesión nueva
 * (`session_index + 1`) y la anterior queda intacta. El total del elemento es la
 * suma de sus sesiones.
 *
 * - `energy_wh_source` / `energy_ah_source`: por magnitud, si el acumulado lo
 *   lleva el odómetro del aparato (`device`) o lo sumamos de las lecturas
 *   (`derived`). Van por separado porque un aparato puede traer odómetro de una
 *   magnitud y no de la otra (el Renogy Rover no mide los Wh de la batería).
 * - `energy_wh_device_total` / `energy_ah_device_total`: el último valor que
 *   reportó el odómetro. El acumulado suma su **avance** y el reinicio se detecta
 *   comparando el odómetro consigo mismo, no contra nuestro total.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy_historical';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Acumulados totales históricos de energía por sesión de encendido de hardware');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('hardware_device_id')
                ->comment('Dispositivo al que pertenece la serie histórica');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedBigInteger('hardware_energy_id')
                ->nullable()
                ->comment('Elemento concreto (canal/rol) al que corresponde el acumulado');
            $table->foreign('hardware_energy_id')
                ->references('id')->on('hardware_energy')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->integer('session_index')
                ->default(1)
                ->comment('Número de sesión. Incrementa si el odómetro del hardware se resetea a 0');

            $table->integer('days_operating')
                ->default(0)
                ->comment('Días acumulados en esta sesión');

            $table->integer('readings_count')
                ->default(0)
                ->comment('Lecturas acumuladas en esta sesión');

            $table->decimal('energy_wh', 16, 4)
                ->default(0)
                ->comment('Total acumulado de energía en esta sesión (Wh)');
            $table->decimal('energy_ah', 14, 4)
                ->default(0)
                ->comment('Total acumulado de amperios-hora en esta sesión (Ah)');

            $table->integer('number_battery_full_charges')
                ->nullable()
                ->default(0)
                ->comment('Ciclos de carga completa acumulados');
            $table->integer('number_battery_over_discharges')
                ->nullable()
                ->default(0)
                ->comment('Ciclos de sobredescarga acumulados');

            $table->decimal('voltage_min', 8, 3)
                ->nullable()
                ->comment('Tensión mínima histórica de la sesión (V)');
            $table->decimal('voltage_max', 8, 3)
                ->nullable()
                ->comment('Tensión máxima histórica de la sesión (V)');

            $table->decimal('amperage_min', 12, 6)
                ->nullable()
                ->comment('Corriente mínima histórica de la sesión (A)');
            $table->decimal('amperage_max', 12, 6)
                ->nullable()
                ->comment('Corriente máxima histórica de la sesión (A)');

            $table->decimal('power_min', 12, 4)
                ->nullable()
                ->comment('Potencia mínima histórica de la sesión (W)');
            $table->decimal('power_max', 12, 4)
                ->nullable()
                ->comment('Potencia máxima histórica de la sesión (W)');

            $table->decimal('temperature_min', 6, 3)
                ->nullable()
                ->comment('Temperatura mínima histórica de la sesión (°C)');
            $table->decimal('temperature_max', 6, 3)
                ->nullable()
                ->comment('Temperatura máxima histórica de la sesión (°C)');

            $table->decimal('battery_min', 8, 3)
                ->nullable()
                ->comment('Tensión mínima de batería histórica de la sesión (V)');
            $table->decimal('battery_max', 8, 3)
                ->nullable()
                ->comment('Tensión máxima de batería histórica de la sesión (V)');

            $table->smallInteger('fan_min')
                ->nullable()
                ->comment('Velocidad/estado mínimo histórico del ventilador');
            $table->smallInteger('fan_max')
                ->nullable()
                ->comment('Velocidad/estado máximo histórico del ventilador');

            $table->string('energy_wh_source', 16)
                ->default('derived')
                ->comment('device = los vatios-hora los lleva el odómetro del aparato | derived = los sumamos de nuestras lecturas.');
            $table->string('energy_ah_source', 16)
                ->default('derived')
                ->comment('device = los amperios-hora los lleva el odómetro del aparato | derived = los sumamos de nuestras lecturas.');

            $table->decimal('energy_wh_device_total', 16, 4)
                ->nullable()
                ->comment('Último total de vatios-hora que reportó el aparato. Sirve para medir su avance y para saber si se ha reiniciado. NULL = nunca ha mandado odómetro de esta magnitud.');
            $table->decimal('energy_ah_device_total', 14, 4)
                ->nullable()
                ->comment('Último total de amperios-hora que reportó el aparato. Mismo uso que el de vatios-hora.');

            $table->timestamps();

            $table->unique(['hardware_energy_id', 'session_index'], 'hardware_energy_historical_energy_session_unique');
            $table->index(['hardware_device_id', 'session_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
