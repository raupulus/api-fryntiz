<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateMeteorologyAemetHighSeasTable.
 *
 * Tabla para almacenar los fenómenos meteorológicos adversos.
 *
 */
class CreateMeteorologyAemetHighSeasTable extends Migration
{
    private $tableName = 'meteorology_aemet_high_seas';
    private $tableComment = 'Datos con la predicción de alta mar';

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


            $table->integer('zone_code')
                ->comment('Código de la zona de altamar');

            $table->timestamp('start_at')
                ->nullable()
                ->comment('Momento de inicio para el periodo de validez de la lectura');

            $table->timestamp('end_at')
                ->comment('Momento de fin para el periodo de validez de la lectura');


            $table->text('text')
                ->nullable()
                ->comment('Contiene la información de la predicción');

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
