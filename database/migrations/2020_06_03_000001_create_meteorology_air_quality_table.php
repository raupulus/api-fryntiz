<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeteorologyAirQualityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meteorology_air_quality', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->decimal('gas_resistance', 22, 11)
                ->comment('Valor de la resistencia del sensor');
            $table->decimal('air_quality', 14, 4)
                ->comment('Resultado del algoritmo para calcular porcentaje de calidad del aire según resistencia, medida en frio y compensación por humedad');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meteorology_air_quality');
    }
}
