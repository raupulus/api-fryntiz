<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAirFlightRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('airflight_routes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('airflight_airplane_id');
            $table->foreign('airflight_airplane_id')
                ->references('id')->on('airflight_airplane')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('squawk')
                ->nullable()
                ->comment('Código de transpondedor seleccionado (Señal squawk en representación octal)');
            $table->string('flight')
                ->nullable()
                ->comment('Nombre del vuelo');

            $table->string('lat')
                ->nullable()
                ->comment('Latitude');

            $table->string('lon')
                ->nullable()
                ->comment('Latitude');

            $table->string('nucp')
                ->nullable()
                ->comment('the NUCp (navigational uncertainty category) reported for the position');

            /*
            $table->string('seen_pos')
                ->nullable()
                ->comment('Tiempo en segundos (antes de ahora) desde el que fue visto por última vez');
            */

            $table->string('altitude')
                ->nullable()
                ->comment('Altitud en pies, o "ground" si está en tierra');

            $table->string('vert_rate')
                ->nullable()
                ->comment('Velocidad vertical en pies/minuto');

            $table->string('track')
                ->nullable()
                ->comment('Track verdadero sobre el suelo en grados (0-359)');

            $table->string('speed')
                ->nullable()
                ->comment('velocidad informada en kt. esto suele ser la velocidad sobre el suelo, pero podría ser ias');

            $table->string('messages')
                ->nullable()
                ->comment('Número total de mensajes de modo s recibidos desde esta aeronave');

            $table->timestamp('seen_at')
                ->nullable()
                ->comment('Momento en el que recibió el último mensaje de este avión');

            $table->string('rssi')
                ->nullable()
                ->comment('rssi promedio reciente (potencia de señal), en dbfs; esto siempre será negativo.');

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
        Schema::dropIfExists('airflight_routes');
    }
}
