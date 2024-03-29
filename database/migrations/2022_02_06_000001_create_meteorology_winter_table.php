<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMeteorologyWinterTable
 */
class CreateMeteorologyWinterTable extends Migration
{
    private $tableName = 'meteorology_winter';
    private $tableComment = 'Datos del viento';

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
            $table->decimal('speed', 14, 4)
                ->comment('Velocidad del viento m/s');
            $table->decimal('average', 14, 4)
                ->comment('Velocidad promedio del viento m/s');
            $table->decimal('min', 14, 4)
                ->comment('Velocidad mínima del viento m/s');
            $table->decimal('max', 14, 4)
                ->comment('Velocidad máxima del viento m/s');
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
