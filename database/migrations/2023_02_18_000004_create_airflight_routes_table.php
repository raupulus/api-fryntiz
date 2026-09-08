<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateAirFlightRoutesTable
 *
 * Unidades de `altitude`/`speed`/`vert_rate`/`rssi`/`lat`/`lon`: contrato
 * definitivo confirmado con el propietario del capturador (2026-09-09) en
 * `docs/info/airflight.md` ("Unidades de airflight_routes"). SI en todo
 * (metros, m/s, grados, dBFS), aunque el decodificador ADS-B (`dump1090`)
 * trabaje internamente en pies/nudos — esa conversión la hace el capturador
 * antes de subir, esta tabla nunca guarda ft/kt. Antes de "corregir" estos
 * comentarios otra vez, lee ese documento: ya se llegó una vez a la
 * conclusión contraria apoyándose en datos históricos corruptos, y era un
 * error (ver la sección "Corrección sobre una confusión propia" de ese
 * mismo documento).
 */
class CreateAirFlightRoutesTable extends Migration
{
    private $tableName = 'airflight_routes';

    private $tableComment = 'Posiciones sucesivas de cada aeronave: la traza del vuelo.';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Posiciones sucesivas de cada aeronave: la traza del vuelo.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario dueño del registro.');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('airplane_id')
                ->comment('Aeronave a la que pertenece esta posición.');
            $table->foreign('airplane_id')
                ->references('id')->on('airflight_airplanes')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo del que procede la lectura.');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
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

            // SI confirmado, no pies/nudos — ver el docblock de la clase
            // antes de tocar estos tres comentarios.
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

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            // Serie temporal: la API filtra por dispositivo y ordena por
            // fecha. Sin este índice cada consulta escanea la tabla entera.
            $table->index(['airplane_id', 'created_at']);
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
