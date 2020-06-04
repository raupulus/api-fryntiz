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
            $table->bigInteger('pulsations')
                ->comment('Cantidad de pulsaciones total de la racha');
            $table->bigInteger('pulsations_special_keys')
                ->comment('Cantidad de pulsaciones para teclas especiales total de la racha');
            $table->decimal('pulsation_average', 15, 5)
                ->comment('Velocidad media de pulsaciones para la racha');
            $table->bigInteger('score')
                ->comment('Puntuación conseguida en esta racha');
            $table->bigInteger('weekday')
                ->comment('Día de la semana (0 es domingo)');
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
