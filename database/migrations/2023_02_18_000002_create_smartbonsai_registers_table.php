<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSmartbonsaiRegistersTable
 */
class CreateSmartbonsaiRegistersTable extends Migration
{
    private $tableName = 'smartplant_registers';
    private $tableComment = 'Registros obtenidos desde los sensores de las plantas';

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
            $table->unsignedBigInteger('plant_id');
            $table->foreign('plant_id')
                ->references('id')->on('smartplant_plants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->integer('uv')
                ->nullable()
                ->comment('Cantidad rayos UV en el ambiente');
            $table->decimal('temperature', 13, 2)
                ->nullable()
                ->comment('Cantidad de temperatura en el ambiente');
            $table->decimal('pressure', 13, 2)
                ->nullable()
                ->comment('Cantidad de temperatura en el ambiente');
            $table->decimal('humidity', 13, 2)
                ->nullable()
                ->comment('Cantidad de humedad en el ambiente');
            $table->decimal('soil_humidity', 13, 2)
                ->comment('Humedad del suelo');
            $table->decimal('soil_humidity', 13, 2)
                ->comment('Humedad del suelo');
            $table->boolean('full_water_tank')
                ->default(false)
                ->comment('Indica si hay agua en el tanque de agua');
            $table->boolean('waterpump_enabled')
                ->default(false)
                ->comment('Indica si se ha activado la bomba de agua');
            $table->boolean('vaporizer_enabled')
                ->default(false)
                ->comment('Indica si se ha activado el vaporizador');
            $table->timestamp('created_at')->nullable();
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
        Schema::dropIfExists($this->tableName);
    }
}
