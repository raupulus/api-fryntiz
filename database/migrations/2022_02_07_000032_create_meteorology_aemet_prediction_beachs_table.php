<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateMeteorologyAemetAdverseEvents.
 *
 * Tabla para almacenar los fenómenos meteorológicos adversos.
 *
 */
class CreateMeteorologyAemetPredictionBeachsTable extends Migration
{
    private $tableName = 'meteorology_aemet_prediction_beachs';
    private $tableComment = 'Predicciones para las playas';

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

            $table->integer('beach_id')
                ->comment('Almacena el identificador que tiene la playa');

            $table->string('name', 255)
                ->nullable()
                ->comment('Nombre de la zona sobre la que guardamos los datos');

            $table->string('slug', 255)
                ->nullable()
                ->comment('Slug creado a partir del nombre recibido en la api, con este se identificará la zona para las consultas');

            $table->integer('city_code')
                ->nullable()
                ->comment('Código de la ciudad asociada');

            $table->smallInteger('sky_morning_status_code')
                ->default(0)
                ->comment('Código de estado para el cielo por la mañana');

            $table->string('sky_morning_status', 255)
                ->default(0)
                ->comment('Descripción del estado para el cielo por la mañana');

            $table->smallInteger('sky_afternoon_status_code')
                ->default(0)
                ->comment('Código de estado para el cielo por la tarde');

            $table->string('sky_afternoon_status', 255)
                ->default(0)
                ->comment('Descripción del estado para el cielo por la tarde');

            $table->text('sky_extra_info')
                ->nullable()
                ->comment('Datos extra sobre la información del cielo');

            $table->smallInteger('wind_morning_status_code')
                ->default(0)
                ->comment('Código de estado para el viento por la mañana');

            $table->string('wind_morning_status', 255)
                ->default('Despejado')
                ->comment('Descripción del estado para el viento por la mañana');

            $table->smallInteger('wind_afternoon_status_code')
                ->default(0)
                ->comment('Código de estado para el viento por la tarde');

            $table->string('wind_afternoon_status', 255)
                ->default('Flojo')
                ->comment('Descripción del estado para el viento por la tarde');

            $table->text('wind_extra_info')
                ->nullable()
                ->comment('Datos extra sobre la información del viento');


            $table->smallInteger('wave_morning_status_code')
                ->default(0)
                ->comment('Código de estado para las olas por la mañana');

            $table->string('wave_morning_status', 255)
                ->default('Débil')
                ->comment('Descripción del estado para las olas por la mañana');

            $table->smallInteger('wave_afternoon_status_code')
                ->default(0)
                ->comment('Código de estado para las olas por la tarde');

            $table->string('wave_afternoon_status', 255)
                ->default('Débil')
                ->comment('Descripción del estado para las olas por la tarde');

            $table->text('wave_extra_info')
                ->nullable()
                ->comment('Datos extra sobre la información las olas');

            $table->smallInteger('temperature_max')
                ->comment('Temperatura máxima para el día');

            $table->text('temperature_max_extra_info')
                ->nullable()
                ->comment('Información extra para la temperatura máxima');


            $table->smallInteger('thermal_sensation_status_code')
                ->comment('Código de estado para la temperatura máxima');

            $table->string('thermal_sensation_status', 255)
                ->comment('Descripción del estado para temperatura máxima');

            $table->text('thermal_sensation_extra_info')
                ->nullable()
                ->comment('Datos extra sobre la información de la temperatura máxima');

            $table->smallInteger('water_temperature')
                ->comment('Temperatura del agua');

            $table->text('water_temperature_extra_info')
                ->nullable()
                ->comment('Información extra sobre la temperatura del agua');

            $table->smallInteger('uv_max')
                ->comment('Radiación UV máxima');

            $table->text('uv_max_extra_info')
                ->nullable()
                ->comment('Información extra sobre la radiación UV máxima');

            $table->date('date');
            $table->timestamp('read_at');
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
        Schema::dropIfExists($this->tableName);
    }
}
