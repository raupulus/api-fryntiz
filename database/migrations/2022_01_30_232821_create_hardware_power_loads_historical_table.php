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
                ->default(0)
                ->comment('Ventilador mínima del ventilador (rpm)');
            $table->integer('fan_avg')
                ->nullable()
                ->default(0)
                ->comment('Velocidad promedio del ventilador (rpm)');
            $table->integer('fan_max')
                ->nullable()
                ->default(0)
                ->comment('Velocidad máxima del ventilador (rpm)');
            $table->decimal('temperature_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura mínima del dispositivo (°C)');
            $table->decimal('temperature_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura promedio del dispositivo (°C)');
            $table->decimal('temperature_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura máxima del dispositivo (°C)');
            $table->decimal('voltage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje mínimo del dispositivo (V)');
            $table->decimal('voltage_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje promedio del dispositivo (V)');
            $table->decimal('voltage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo del dispositivo (V)');
            $table->decimal('amperage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje mínimo del dispositivo (A)');
            $table->decimal('amperage_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje promedio del dispositivo (A)');
            $table->decimal('amperage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje máximo del dispositivo (A)');

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
