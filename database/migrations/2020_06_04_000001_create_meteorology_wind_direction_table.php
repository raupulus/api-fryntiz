<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeteorologyWindDirectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meteorology_wind_direction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('resistance', 22, 11)
                ->comment('Valor de la resistencia usado para calcular la posición del viento');
            $table->string('direction', 255)
                ->comment('Dirección del viento (N, S, E, O, NE, NO, SE, SO)');
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
        Schema::dropIfExists('meteorology_wind_direction');
    }
}
