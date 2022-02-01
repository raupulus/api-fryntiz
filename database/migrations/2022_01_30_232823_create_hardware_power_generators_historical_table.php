<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerGeneratorsHistoricalTable
 *
 * Carga generada de energía por dispositivo de hardware en el día.
 */
class CreateHardwarePowerGeneratorsHistoricalTable extends Migration
{
    private $tableName = 'hardware_power_generators_historical';
    private $tableComment = 'Carga de energía generada por dispositivo de hardware en total.';

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
            $table->integer('total_days_operating')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('total_number_battery_over_discharges')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('total_number_battery_full_charges')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('total_charging_amp_hours')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('total_discharging_amp_hours')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('cumulative_power_generation')
                ->nullable()
                ->default(0)
                ->comment('');
            $table->integer('cumulative_power_consumption')
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
