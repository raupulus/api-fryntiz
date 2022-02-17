<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateAirFlightAirplanesTable
 */
class CreateAirFlightAirplanesTable extends Migration
{
    private $tableName = 'airflight_airplanes';
    private $tableComment = 'Almacena los aviones.';

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
            $table->string('icao')
                ->nullable()
                ->comment('Código ICAO 24 bits (6 dígitos hexadecimales)');

            $table->string('country')
                ->nullable()
                ->comment('País de origen del avión');

            $table->string('category')
                ->nullable()
                ->comment('Categoría del avión');

            $table->timestamp('seen_first_at')
                ->nullable()
                ->comment('Indica momento en el que se ha visto por primera vez');

            $table->timestamp('seen_last_at')
                ->nullable()
                ->comment('Indica momento en el que se ha visto por última vez');

            $table->timestamp('route_last_at')
                ->nullable()
                ->comment('El momento del último registro con ruta válida');

            $table->string('flag')
                ->nullable()
                ->comment('Imagen de la bandera');

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
        Schema::dropIfExists($this->tableName, function (Blueprint
                                                              $table) {
            $table->dropForeign(['user_id']);
        });
    }
}
