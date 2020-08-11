<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmartbonsaiRegistersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smartbonsai_registers', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('smartbonsai_plant_id');
            $table->foreign('smartbonsai_plant_id')
                ->references('id')->on('smartbonsai_plants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->integer('uv')
                ->comment('Cantidad rayos UV en el ambiente');
            $table->decimal('temperature', 13, 2)
                ->comment('Cantidad de temperatura en el ambiente');
            $table->decimal('humidity', 13, 2)
                ->comment('Cantidad de humedad en el ambiente');
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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('smartbonsai_registers');
    }
}
