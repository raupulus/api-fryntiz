<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateMeteorologyAirQualityTable
 */
class CreateMeteorologyAirQualityTable extends Migration
{
    private $tableName = 'meteorology_air_quality';

    private $tableComment = 'Datos de la calidad del aire';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Datos de la calidad del aire');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
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
            $table->decimal('gas_resistance', 22, 11)
                ->comment('Valor de la resistencia del sensor');
            $table->decimal('air_quality', 14, 4)
                ->comment('Resultado del algoritmo para calcular porcentaje de calidad del aire según resistencia, medida en frio y compensación por humedad');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación');

            // Serie temporal: la API filtra por dispositivo y ordena por
            // fecha. Sin este índice cada consulta escanea la tabla entera.
            $table->index(['hardware_device_id', 'created_at']);
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
