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
class CreateMeteorologyAemetContaminationTable extends Migration
{
    private $tableName = 'meteorology_aemet_contamination';
    private $tableComment = 'Registro de contaminación';

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

            $table->decimal('so2', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('no', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('no2', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('o3', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('pm10', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('wind_speed', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('wind_direction', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('temperature', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('humidity', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('pressure', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('radiation_global', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');

            $table->decimal('rain', 6, 2)
                ->nullable()
                ->comment('Valor de la lectura');


            $table->date('date')
                ->comment('Fecha de la lectura');

            $table->time('time')
                ->comment('Hora de la lectura');
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
