<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMeteorologyTvocTable
 */
class CreateMeteorologyRainTable extends Migration
{
    private $tableName = 'meteorology_rain';
    private $tableComment = 'Datos para registros de lluvia';

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
            $table->decimal('rain', 11, 1)
                ->comment('Agua caída en el último periodo de tiempo (mm)');

            $table->decimal('rain_intensity', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Intensidad de la lluvia en mm/h');

            $table->decimal('rain_month', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Lluvia acumulada en el mes en mm');



            $table->decimal('moisture', 11, 1)
                ->comment('Vapor de agua en el aire (g/m3)');

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
