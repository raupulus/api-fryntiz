<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateMeteorologyAEMETAdverseEvents.
 *
 * Tabla para almacenar los fenómenos meteorológicos adversos.
 *
 */
class CreateMeteorologyAEMETAdverseEvents extends Migration
{
    private $tableName = 'meteorology_aemet_adverse_events';
    private $tableComment = 'Datos de fenómenos meteorológicos adversos';

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

            $table->string('name', 255)
                ->comment('Nombre de la zona sobre la que guardamos los datos');

           $table->string('slug', 255)
               ->comment('Slug creado a partir del nombre recibido en la api, con este se identificará la zona para las consultas');

           $table->text('polygon')
               ->nullable()
               ->comment('Array de Coordenadas para polígonos');

           $table->text('others_fields_json')
               ->nullable()
               ->comment('Estos son campos que no están definidos en la api pero pueden llegar, hasta ahora no hay forma de identificar un fenómeno con valores númericos para interpretarlos');

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
        Schema::dropIfExists($this->tableName);
    }
}
