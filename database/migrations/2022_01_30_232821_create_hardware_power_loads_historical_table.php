<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerLoadsHistoricalTable
 *
 * Carga consumida de energía por dispositivo de hardware en total.
 */
class CreateHardwarePowerLoadsHistoricalTable extends Migration
{
    private $tableName = 'hardware_power_loads_historical';
    private $tableComment = 'Carga de energía consumida por dispositivo de hardware en el total.';

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
                ->comment('Velocidad mínima de ventilador (rpm)');
            $table->integer('fan_max')
                ->nullable()
                ->comment('Velocidad máxima de ventilador (rpm)');
            $table->decimal('temperature_min', 6, 3)
                ->nullable()
                ->default(0)
                ->comment('Temperatura mínima (°C)');
            $table->decimal('temperature_max', 6, 3)
                ->nullable()
                ->default(0)
                ->comment('Temperatura máxima (°C)');
            $table->decimal('voltage_min', 8, 3)
                ->nullable()
                ->default(0)
                ->comment('Volts mínimo (V)');
            $table->decimal('voltage_max', 8, 3)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo (V)');
            $table->decimal('battery_min', 8, 3)
                ->nullable()
                ->comment('Voltaje mínimo de batería (V)');
            $table->decimal('battery_max', 8, 3)
                ->nullable()
                ->comment('Voltaje máximo de batería (V)');
            $table->decimal('amperage_min', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Amperaje mínimo (A)');
            $table->decimal('amperage_max', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Amperaje máximo (A)');
            $table->decimal('amperage', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Amperaje total (A)');
            $table->decimal('power_min', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Potencia mínima (W)');
            $table->decimal('power_max', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Potencia máxima (W)');
            $table->decimal('power', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Potencia total (W)');
            $table->integer('days_operating')
                ->nullable()
                ->default(0)
                ->comment('Número de días que el dispositivo ha estado operativo');
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
