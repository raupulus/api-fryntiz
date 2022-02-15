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
            $table->integer('fan_avg')
                ->nullable()
                ->default(0)
                ->comment('Velocidad promedio de ventilador (rpm)');
            $table->integer('fan_max')
                ->nullable()
                ->default(0)
                ->comment('Velocidad máxima de ventilador (rpm)');
            $table->decimal('temperature_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura mínima (°C)');
            $table->decimal('temperature_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperalta promedio (°C)');
            $table->decimal('temperature_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura máxima (°C)');
            $table->decimal('voltage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Volts mínimo (V)');
            $table->decimal('voltage_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje promedio (V)');
            $table->decimal('voltage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo (V)');
            $table->decimal('amperage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje mínimo (A)');
            $table->decimal('amperage_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje promedio (A)');
            $table->decimal('amperage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Amperaje máximo (A)');
            $table->decimal('power_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia mínima (W)');
            $table->decimal('power_avg', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia promedio (W)');
            $table->decimal('power_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia máxima (W)');
            $table->date('date')
                ->nullable()
                ->comment('Fecha de medición');

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
