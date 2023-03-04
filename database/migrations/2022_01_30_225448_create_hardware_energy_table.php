<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwareComponentsTable
 *
 * Tabla asociando componentes concretos a los dispositivos.
 */
class CreateHardwareEnergyTable extends Migration
{
    private $tableName = 'hardware_energy';
    private $tableComment = 'Asocia dispositivos que monitorizan consumo o generación de energía con sus dispositivos monitorizados';

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
                ->comment('Dispositivo que se usa como monitor');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('hardware_device_monitorized_id')
                ->nullable()
                ->comment('Dispositivo que está siendo monitorizado');
            $table->foreign('hardware_device_monitorized_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->boolean('is_generator')
                ->default(false)
                ->nullable()
                ->comment('Indica si el dispositivo monitorizado es un generador de energía o un consumidor');

            $table->smallInteger('sensor_position')
                ->nullable()
                ->comment('Posición del sensor en el dispositivo monitorizado');

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
            $table->dropForeign(['hardware_available_component_id']);
        });
    }
}
