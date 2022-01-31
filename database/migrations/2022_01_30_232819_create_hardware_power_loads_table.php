<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->integer('fan', 11)
                ->nullable()
                ->default(0)
                ->comment('Velocidad del ventilador en RPM, EJ:3812. Será 0 por defecto');
            $table->integer('temperature', 11)
                ->nullable()
                ->default(0)
                ->comment('Temperatura del dispositivo en grados centígrados, EJ:38. Será 0 por defecto');
            $table->integer('voltage', 11)
                ->nullable()
                ->default(0)
                ->comment('Voltaje de la batería en milivoltios, EJ:3812. Será 0 por defecto');
            $table->integer('amperage', 11)
                ->nullable()
                ->default(0)
                ->comment('Intensidad de la batería en milivoltios, EJ:3812. Será 0 por defecto');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName, function (Blueprint $table) {
            $table->dropForeign(['???']);
        });
    }
}
