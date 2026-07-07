<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `solar_charges` para almacenar lecturas de cargadores solares.
 * Registra voltaje, corriente y potencia de batería, panel fotovoltaico y carga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solar_charges', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('hardware_device_id')
                ->comment('Dispositivo hardware asociado al cargador solar');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->date('date')->nullable()->comment('Fecha de la lectura');
            $table->timestamp('read_at')->nullable()->comment('Timestamp exacto de la lectura');

            $table->string('hardware', 255)->nullable()->comment('Nombre o modelo del hardware del cargador');
            $table->string('version', 255)->nullable()->comment('Versión del firmware o software');
            $table->string('serial_number', 255)->nullable()->comment('Número de serie del dispositivo');
            $table->string('battery_type', 255)->nullable()->comment('Tipo de batería (LiFePO4, AGM, etc.)');

            $table->decimal('battery_voltage', 8, 2)->nullable()->comment('Voltaje de la batería en voltios');
            $table->decimal('battery_current', 8, 2)->nullable()->comment('Corriente de la batería en amperios');
            $table->decimal('battery_power', 8, 2)->nullable()->comment('Potencia de la batería en vatios');
            $table->smallInteger('battery_soc')->nullable()->comment('Estado de carga de la batería (0-100%)');
            $table->smallInteger('battery_percentage')->nullable()->comment('Porcentaje de batería (0-100)');

            $table->decimal('pv_voltage', 8, 2)->nullable()->comment('Voltaje del panel fotovoltaico en voltios');
            $table->decimal('pv_current', 8, 2)->nullable()->comment('Corriente del panel fotovoltaico en amperios');
            $table->decimal('pv_power', 8, 2)->nullable()->comment('Potencia del panel fotovoltaico en vatios');

            $table->decimal('load_voltage', 8, 2)->nullable()->comment('Voltaje de la carga en voltios');
            $table->decimal('load_current', 8, 2)->nullable()->comment('Corriente de la carga en amperios (load_amperage)');
            $table->decimal('load_power', 8, 2)->nullable()->comment('Potencia de la carga en vatios');

            $table->decimal('energy_voltage', 8, 2)->nullable()->comment('Voltaje de energía generada en voltios');
            $table->decimal('energy_amperage', 8, 2)->nullable()->comment('Corriente de energía generada en amperios');
            $table->decimal('energy_power', 8, 2)->nullable()->comment('Potencia de energía generada en vatios');

            $table->decimal('temperature', 5, 2)->nullable()->comment('Temperatura del controlador en grados Celsius');

            $table->timestamps();

            $table->index(['hardware_device_id', 'created_at'], 'solar_charges_device_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_charges');
    }
};
