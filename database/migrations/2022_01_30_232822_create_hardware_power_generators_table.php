<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerGeneratorsTable
 *
 * Carga generada de energía por dispositivo de hardware en el momento.
 */
class CreateHardwarePowerGeneratorsTable extends Migration
{
    private $tableName = 'hardware_power_generators';
    private $tableComment = 'Carga de energía generada por dispositivo de hardware en el momento.';

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
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->integer('load_current')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('load_voltage')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('load_power')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('battery_voltage')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('battery_temperature')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('battery_percentage')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('charging_status')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('charging_status_label')
                ->nullable()
                ->default(0)
                ->comment('(mppt, absortion...)');
            $table->integer('origin_current')
                ->nullable()
                ->default(0)
                ->comment('Fuente del generador, por ejemplo solar');
            $table->integer('origin_voltage')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('origin_power')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->boolean('light_status')
                ->nullable()
                ->default(false)
                ->comment('');
            $table->integer('light_brightness')
                ->nullable()
                ->default(0)
                ->comment('');

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
        Schema::dropIfExists($this->tableName, function (Blueprint $table) {
            $table->dropForeign(['hardware_device_id']);
        });
    }
}
