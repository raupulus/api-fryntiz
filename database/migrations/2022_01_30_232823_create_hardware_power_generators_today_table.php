<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerGeneratorsTodayTable
 *
 * Carga generada de energía por dispositivo de hardware en el día.
 */
class CreateHardwarePowerGeneratorsTodayTable extends Migration
{
    private $tableName = 'hardware_power_generators_today';
    private $tableComment = 'Carga de energía generada por dispositivo de hardware en el día.';

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
            $table->decimal('battery_min_voltage', 10, 2)
                ->nullable()
                ->default(0.0)
                ->comment('Voltaje de la batería mínimo en el día');
            $table->decimal('battery_max_voltage', 10, 2)
                ->nullable()
                ->default(0.0)
                ->comment('Voltaje de la batería máximo en el día');
            $table->integer('max_charging_power')
                ->nullable()
                ->default(0)
                ->comment('Potencia máxima de carga en el día actual');
            $table->decimal('max_charging_current', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Intensidad máxima de carga en el día actual');
            $table->decimal('max_discharging_current', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Devuelve la intensidad máxima de descarga en el día actual');
            $table->decimal('charging_amp_hours', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Devuelve la carga en Ah para el día actual');
            $table->decimal('discharging_amp_hours', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Devuelve la descarga en Ah para el día actual');
            $table->integer('power_generation')
                ->nullable()
                ->default(0)
                ->comment('Potencia generada en el día actual');
            $table->integer('power_consumption')
                ->nullable()
                ->default(0)
                ->comment('Potencia consumida en el día actual');
            $table->date('date')
                ->nullable()
                //->default(Today())
                ->comment('Fecha en del registro');

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
