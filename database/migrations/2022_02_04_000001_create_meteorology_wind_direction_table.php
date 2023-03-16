<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMeteorologyWindDirectionTable
 */
class CreateMeteorologyWindDirectionTable extends Migration
{
    private $tableName = 'meteorology_wind_direction';
    private $tableComment = 'Datos de dirección del viento';

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
            $table->integer('grades')
                ->comment('Grados según puntos cardinales basados en resistencia, 0 es Norte, 90 es Este, 180 es Sur, 270 es Oeste');
            $table->decimal('resistance', 22, 11)
                ->comment('Valor de la resistencia usado para calcular la posición del viento, valor entre 0 y 65535')
                ->nullable();

            $table->string('direction', 255)
                ->comment('Dirección del viento (N, S, E, O, NE, NO, SE, SO)');
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
