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
class CreateMeteorologyAemetPredictionsTable extends Migration
{
    private $tableName = 'meteorology_aemet_predictions';
    private $tableComment = 'Predicción por horas de información de hora en hora hasta 48 horas. Se generan de forma automática mediante el tratamiento estadístico de los resultados de modelos numéricos de predicción';

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

            $table->string('city', 255)
                ->nullable()
                ->comment('Ciudad sobre la que se piden las predicciones');

            $table->string('province', 255)
                ->nullable()
                ->comment('Provincia en la que se encuentra la ciudad');

            $table->string('sky_status', 255)
                ->nullable()
                ->comment('Descripción del estado del cielo');

            $table->string('sky_status_code', 255)
                ->nullable()
                ->comment('Código del estado del Cielo');

            $table->decimal('rain', 6, 2)
                ->nullable()
                ->comment('Cantidad total de precipitación durante la hora anterior (mm)');

            $table->integer('rain_prob')
                ->nullable()
                ->comment('Valor de la probabilidad de precipitación (%)');

            $table->integer('storm_prob')
                ->nullable()
                ->comment('Valor de la probabilidad de tormenta (%)');

            $table->decimal('snow', 6, 2)
                ->nullable()
                ->comment('Cantidad total de nieve que se prevé que caiga durante la hora anterior (mm)');

            $table->integer('snow_prob')
                ->nullable()
                ->comment('Valor de la probabilidad de precipitación de nieve (%)');

            $table->decimal('temperature', 6, 2)
                ->nullable()
                ->comment('Valor de la temperatura (ºC)');

            $table->decimal('thermal_sensation', 6, 2)
                ->nullable()
                ->comment('Sensación térmica (ºC)');

            $table->integer('humidity')
                ->nullable()
                ->comment('Valor de la humedad relativa (%)');


            $table->timestamp('sunrise');
            $table->timestamp('sunset');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->timestamp('day_start_at');
            $table->timestamp('day_end_at');
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
