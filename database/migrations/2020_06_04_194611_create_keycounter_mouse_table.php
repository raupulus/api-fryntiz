<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKeycounterMouseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keycounter_mouse', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->timestamp('start_at')
                ->comment('Momento de iniciar la racha');
            $table->timestamp('end_at')
                ->comment('Momento del final de la racha');
            $table->bigInteger('duration')
                ->comment('Duración en Segundos de la racha');
            $table->bigInteger('clicks_left')
                ->comment('Cantidad de clicks derecho');
            $table->bigInteger('clicks_right')
                ->comment('Cantidad de clicks izquierdo');
            $table->bigInteger('clicks_middle')
                ->comment('Cantidad de clicks centrales');
            $table->bigInteger('total_clicks')
                ->comment('Cantidad de clicks total de la racha');
            $table->bigInteger('clicks_average')
                ->comment('Cantidad de cliks medio de la racha');
            $table->bigInteger('weekday')
                ->comment('Día de la semana (0 es domingo)');
            $table->string('device_id')
                ->comment('Identificador del dispositivo asociado');
            $table->string('device_name')
                ->comment('Nombre del dispositivo asociado');
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
        Schema::dropIfExists('keycounter_mouse');
    }
}
