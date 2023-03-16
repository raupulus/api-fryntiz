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
class CreateMeteorologyAemetSunRadiationTable extends Migration
{
    private $tableName = 'meteorology_aemet_sun_radiation';
    private $tableComment = 'Datos horarios (HORA SOLAR VERDADERA) acumulados de radiación  global, directa, difusa e infrarroja, y datos semihorarios  (HORA SOLAR VERDADERA) acumulados de radiación ultravioleta eritemática.Datos diarios acumulados  de radiación global, directa, difusa, ultravioleta eritemática e infrarroja. Datos sometidos a controles automáticos de calidad en tiempo real, no puede garantizarse la ausencia de errores';

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

            $table->string('station_code', 255)
                ->comment('Código de la estación, indicativo Climatológico Estación');

            $table->string('type_global', 255)
                ->comment('Variable medida global');

            $table->string('type_diffuse', 255)
                ->comment('Variable medida difusa');

            $table->string('type_direct', 255)
                ->comment('Variable medida directa');

            $table->string('type_uv_eritematica', 255)
                ->comment('Variable medida UV Eritemática');

            $table->string('type_infrarroja', 255)
                ->comment('Variable medida Infrarroja');

            $table->string('real_solar_hour_global', 255)
                ->comment('Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Global. Unidad de medida 10*kJ/m2');

            $table->string('real_solar_hour_diffuse', 255)
                ->comment('Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Difusa. Unidad de medida 10*kJ/m2');

            $table->string('real_solar_hour_direct', 255)
                ->comment('Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Directa. Unidad de medida 10*kJ/m2');

            $table->string('sum_global', 255)
                ->nullable()
                ->comment('Radiación diaria acumulada. Suma Global. Unidad de medida 10*kJ/m2');

            $table->string('sum_diffuse', 255)
                ->nullable()
                ->comment('Radiación diaria acumulada. Suma Difusa. Unidad de medida 10*kJ/m2');

            $table->string('sum_direct', 255)
                ->nullable()
                ->comment('Radiación diaria acumulada. Suma Directa. Unidad de medida 10*kJ/m2');

            $table->string('real_solar_hour_uver', 255)
                ->comment('Radiación semihoraria acumulada entre: (hora:minutos indicados - 30 minutos y (hora:minutos indicados) entre las 4:30 y las 20 Hora  Solar Verdadera. Variables: Radiación Ultravioleta Eritemática. Unidad de medida J/m2');

            $table->string('sum_uver', 255)
                ->nullable()
                ->comment('Radiación diaria acumulada. Variables: Radiación Ultravioleta Eritemática.  Unidad de medida J/m2');

            $table->string('real_solar_hour_infrared', 255)
                ->nullable()
                ->comment('Radiación horaria acumulada entre (hora indicada -1) y (hora indicada) entre las 1 y las 24 Hora Solar Verdadera. Variables: Radiación Infrarroja. Unidad de medida 10*kJ/m2');

            $table->string('sum_infrared', 255)
                ->nullable()
                ->comment('Radiación diaria acumulada. Variables: Radiación Infrarroja. Unidad de medida 10*kJ/m2');


            $table->timestamp('start_at');
            $table->timestamp('end_at');
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
