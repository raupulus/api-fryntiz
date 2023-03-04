<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerLoadsTodayTable
 *
 * Carga consumida de energía por dispositivo de hardware en el día.
 */
class CreateHardwarePowerLoadsTodayTable extends Migration
{
    private $tableName = 'hardware_power_loads_today';
    private $tableComment = 'Carga de energía consumida por dispositivo de hardware en el día.';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->integer('fan_min')
                ->nullable()
                ->default(0)
                ->comment('Velocidad mínima de ventilador (rpm)');
            $table->integer('fan_max')
                ->nullable()
                ->default(0)
                ->comment('Velocidad máxima de ventilador (rpm)');
            $table->decimal('temperature_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura mínima (°C)');
            $table->decimal('temperature_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura máxima (°C)');
            $table->decimal('voltage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Volts mínimo (V)');
            $table->decimal('voltage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo (V)');
            $table->decimal('battery_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje mínimo de batería (V)');
            $table->decimal('battery_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo de batería (V)');
            $table->integer('battery_percentage_min')
                ->nullable()
                ->default(0)
                ->comment('Porcentaje de batería mínimo (%)');
            $table->integer('battery_percentage_max')
                ->nullable()
                ->default(0)
                ->comment('Porcentaje de batería máximo (%)');
            $table->decimal('amperage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje mínimo (A)');
            $table->decimal('amperage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje máximo (A)');
            $table->double('amperage')
                ->nullable()
                ->default(0)
                ->comment('Amperaje total (Ah)');
            $table->decimal('power_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia mínima (W)');
            $table->decimal('power_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia máxima (W)');
            $table->decimal('power', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia total (W)');
            $table->date('date')
                ->nullable()
                ->comment('Fecha de medición');
            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de la última lectura');

            $table->timestamps();

        });

        DB::statement("COMMENT ON TABLE {$this->tableName} IS '{$this->tableComment}'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName, function (Blueprint $table) {
            $table->dropForeign(['hardware_device_id']);
        });
    }
}
