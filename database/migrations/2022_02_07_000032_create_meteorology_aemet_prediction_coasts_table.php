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
class CreateMeteorologyAemetPredictionCoastsTable extends Migration
{
    private $tableName = 'meteorology_aemet_prediction_coasts';
    private $tableComment = 'Datos de predicción para las costas';

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

            $table->timestamp('start_at')
                ->nullable()
                ->comment('Momento de inicio para el periodo de validez de la lectura');

            $table->timestamp('end_at')
                ->comment('Momento de fin para el periodo de validez de la lectura');

            $table->string('general_id', 255)
                ->comment('Código de la zona de playa');

            $table->string('general_name', 255)
                ->nullable()
                ->comment('Nombre de la zona de playa');

            $table->string('general_slug', 255)
                ->nullable()
                ->comment('Slug de la zona de playa');

            $table->text('general_text')
                ->nullable()
                ->comment('Texto de la zona de playa');

            $table->string('zone_id', 255)
                ->nullable()
                ->comment('Código de la zona de playa');


            $table->string('zone_name', 255)
                ->nullable()
                ->comment('Nombre de la zona de playa');

            $table->string('zone_slug', 255)
                ->nullable()
                ->comment('Slug de la zona de playa');


            $table->string('subzone_id', 255)
                ->comment('Código de la subzona de playa');


            $table->string('subzone_name', 255)
                ->nullable()
                ->comment('Nombre de la subzona de playa');

            $table->string('subzone_slug', 255)
                ->nullable()
                ->comment('Slug de la subzona de playa');

            $table->text('subzone_text')
                ->nullable()
                ->comment('Texto de la subzona de playa');

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
