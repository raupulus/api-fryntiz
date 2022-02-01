<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerLoadsHistoricalTable
 *
 * Carga consumida de energía por dispositivo de hardware en total.
 */
class CreateHardwarePowerLoadsHistoricalTable extends Migration
{
    private $tableName = 'hardware_power_loads_historical';
    private $tableComment = 'Carga de energía consumida por dispositivo de hardware en el total.';

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
            $table->integer('fan_min')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('fan_average')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('fan_max')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('temperature_min')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('temperature_average')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('temperature_max')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('voltage_min')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('voltage_average')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('voltage_max')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('amperage_min')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('amperage_average')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('amperage_max')
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
