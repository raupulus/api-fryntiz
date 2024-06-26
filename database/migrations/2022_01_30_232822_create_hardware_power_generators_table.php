<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerGeneratorsTable
 *
 * Carga generada de energía por dispositivo de hardware en el momento.
 */
class CreateHardwarePowerGeneratorsTable extends Migration
{
    private $tableName = 'hardware_power_generators';
    private $tableComment = 'Carga de energía generada por dispositivo de hardware en el momento.';

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
            $table->decimal('battery_voltage', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje de la batería');
            $table->decimal('battery_temperature', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura de la batería en su exterior (sensor externo a la batería)');
            $table->integer('battery_percentage')
                ->nullable()
                ->default(0)
                ->comment('Porcentaje de carga en la batería (0-100)');
            $table->integer('charging_status')
                ->nullable()
                ->default(0)
                ->comment('El modo de cargar batería en el momento, código interno del fabricante (1,2,3...).');
            $table->string('charging_status_label', 255)
                ->nullable()
                ->default('deactivated')
                ->comment('El modo de cargar batería en el momento. (deactivated, activated, mppt, equalizing, boost, floating, current limiting)');
            $table->decimal('amperage', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('La intensidad de carga que está generando el generador.');
            $table->decimal('voltage', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('El voltaje de carga que está generando el generador.');
            $table->decimal('power', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('La potencia de carga que está generando el generador.');
            $table->boolean('light_status')
                ->nullable()
                ->default(false)
                ->comment('Indica si hay luz de calle mediante booleano 0|1.');
            $table->integer('light_brightness')
                ->nullable()
                ->default(0)
                ->comment('Devuelve el porcentaje brillo de la luz de calle (0-100%).');

            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de lectura');

            $table->timestamps();
            $table->softDeletes();
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
