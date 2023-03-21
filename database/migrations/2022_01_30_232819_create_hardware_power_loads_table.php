<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerLoadsTable
 *
 * Consumo de energía de todos los dispositivos.
 * Se usa para almacenar el consumo de energía de un dispositivo.
 */
class CreateHardwarePowerLoadsTable extends Migration
{

    private $tableName = 'hardware_power_loads';
    private $tableComment = 'Almacena el consumo de energía de un dispositivo';

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
            $table->integer('fan')
                ->nullable()
                ->comment('Velocidad del ventilador en RPM, EJ:3812. Será null si no hay ventilador');
            $table->decimal('temperature', 6, 3)
                ->nullable()
                ->comment('Temperatura del dispositivo en grados centígrados, EJ:38.31.');
            $table->decimal('voltage', 8,3)
                ->nullable()
                ->comment('Voltaje de la batería en voltios, EJ:12.37.');
            $table->double('amperage')
                ->nullable()
                ->comment('Intensidad de la batería en ah, EJ:1.54.');
            $table->double('power')
                ->nullable()
                ->comment('Potencia de la batería en watios, EJ:13.81.');
            $table->decimal('battery_voltage', 8, 3)
                ->nullable()
                ->comment('Voltaje de la batería. Será 0 por defecto');
            $table->integer('battery_percentage')
                ->nullable()
                ->comment('Porcentaje de carga en la batería (0-100)');
            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de lectura');

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
