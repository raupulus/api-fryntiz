<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMeteorologyResumeHistoricalTable
 */
class CreateMeteorologyResumeHistoricalTable extends Migration
{
    private $tableName = 'meteorology_resume_historical';
    private $tableComment = 'Resumen meteorológico histórico';

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
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->decimal('air_quality', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Resultado del algoritmo para calcular porcentaje de calidad del aire según resistencia, medida en frio y compensación por humedad');
            $table->decimal('eco2', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Partículas en el aire. Valor entre 400ppm y 8192ppm');
            $table->decimal('humidity', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Humedad relativa. Valor entre 0 y 100');
            $table->decimal('light', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Índice de luz');
            $table->decimal('pressure', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Presión atmosférica.');
            $table->decimal('temperature', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Temperatura en grados centígrados.');
            $table->decimal('tvoc', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Volatilidad tóxica. Valor entre  0ppb y 1187ppb');
            $table->decimal('uv_index', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Índice ultravioleta. Valor entre 0 y 11');
            $table->decimal('uva', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Índice ultravioleta. Valor entre 0 y 11');
            $table->decimal('uvb', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Índice ultravioleta. Valor entre 0 y 11');
            $table->decimal('wind_direction', 14, 4)
                ->nullable()
                ->comment('Dirección del viento (N, S, E, O, NE, NO, SE, SO)');
            $table->decimal('wind_speed', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Velocidad del viento en km/h');
            $table->decimal('wind_speed_max', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Racha máxima del viento en km/h');
            $table->decimal('wind_speed_min', 14, 4)
                ->nullable()
                ->default(0)
                ->comment('Racha mínima del viento en km/h');
            $table->integer('lightning')
                ->nullable()
                ->default(0)
                ->comment('Cantidad de rayos');
            $table->decimal('lightning_distance', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Distancia media de los rayos');
            $table->decimal('lightning_intensity', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Intensidad media de los rayos');
            $table->decimal('rain', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Lluvia acumulada en mm');
            $table->decimal('rain_intensity', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Intensidad de la lluvia en mm/h');

            $table->timestamp('created_at')->nullable();
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
            $table->dropForeign(['hardware_device_id']);
        });
    }
}
