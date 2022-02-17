<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateAirFlightRoutesTable
 */
class CreateAirFlightRoutesTable extends Migration
{
    private $tableName = 'airflight_routes';
    private $tableComment = 'Registros de posiciones de aeronaves';

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
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('airplane_id');
            $table->foreign('airplane_id')
                ->references('id')->on('airflight_airplanes')
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
            $table->dropForeign(['user_id']);
        });
    }
}
