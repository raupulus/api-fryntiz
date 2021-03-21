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

            $table->float('lat')
                ->nullable()
                ->comment('Latitud');

            $table->float('lon')
                ->nullable()
                ->comment('Longitud');

            $table->float('altitude')
                ->nullable()
                ->comment('Altitud en metros');

            $table->float('vert_rate')
                ->nullable()
                ->comment('Velocidad vertical en metros por segundo');

            $table->integer('track')
                ->nullable()
                ->comment('Track verdadero sobre el suelo en grados (0-359)');

            $table->float('speed')
                ->nullable()
                ->comment('Velocidad en metros por segundos');

            $table->timestamp('seen_at')
                ->nullable()
                ->comment('Momento en el que recibió el último mensaje de este avión');

            $table->integer('messages')
                ->nullable()
                ->comment('Número total de mensajes de modo s recibidos desde esta aeronave');

            $table->float('rssi')
                ->nullable()
                ->comment('rssi promedio reciente (potencia de señal), en dbfs; esto siempre será negativo.');

            $table->string('emergency')
                ->nullable()
                ->comment('Indica si hay señal de emergencia');

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
