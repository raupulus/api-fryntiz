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
class CreateMeteorologyAemetOzoneTable extends Migration
{
    private $tableName = 'meteorology_aemet_ozone';
    private $tableComment = 'Registro de la capa de ozono mediante un lanzamiento de ozonosonda';

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

            $table->integer('height')
                ->comment('Altura en metros alcanzada por el globo en metros geopotenciales');

            $table->integer('humidity')
                ->comment('Humedad relativa en %');

            $table->decimal('pressure', 6,2)
                ->comment('Presión atmosférica en hPa');

            $table->decimal('temperature', 6,2)
                ->comment('Temperatura en el aire en grados centígrados ºC');

            $table->decimal('temperature_virtual', 6,2)
                ->comment('La temperatura que tendría el aire seco" en ºC');

            $table->decimal('diff_temperature_dew_point', 6,2)
                ->comment('Diferencia entre la temperatura y el punto de rocío en ºC');

            $table->decimal('diff_temperature_per_height_km', 6,2)
                ->comment('Temperatura entre 2 puntos a 1 km de diferencia en altura ascendente, unidad de medida ºC/km (grados centígrados por kilómetro subido)');

            $table->decimal('rate_of_elevation', 6,2)
                ->comment('Velocidad de ascenso en m/s de la ozonosonda');

            $table->decimal('ozone_partial_pressure', 6,2)
                ->comment('Presión parcial de ozono en mPa, presión de ozono si se eliminaran todos los componentes de la mezcla y sin variación de temperatura');

            $table->decimal('device_internal_temperature', 6,2)
                ->nullable()
                ->comment('Temperatura interna del dispositivo en ºC');


            $table->decimal('ozone_integrated', 12,6)
                ->comment('Concentración de ozono en Dobson (Dobson es una unidad de medida de concentración de ozono en la atmósfera terrestre)');

            $table->decimal('ozone_residual', 12,6)
                ->comment('Ozono residual de la columna. Residuo de ozono en Dobson (Dobson es una unidad de medida de concentración de ozono en la atmósfera terrestre)');

            $table->integer('time_min')
                ->comment('Minutos desde el lanzamiento del sondeo');

            $table->integer('time_s')
                ->comment('Segundos desde el lanzamiento del sondeo');

            $table->timestamp('ozone_probe_read_at')
                ->comment('Fecha y hora de la lectura de la ozonosonda');

            $table->timestamp('ozone_probe_launch_at')
                ->comment('Fecha y hora del lanzamiento de la ozonosonda');

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
