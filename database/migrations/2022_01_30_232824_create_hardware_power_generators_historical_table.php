<?php

declare(strict_types=1);

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
            $table->comment('Carga de energía generada por dispositivo de hardware en total.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->integer('days_operating')
                ->nullable()
                ->default(0)
                ->comment('Número de días que el dispositivo ha estado operativo');
            $table->integer('number_battery_over_discharges')
                ->nullable()
                ->default(0)
                ->comment('Número de veces que se ha vaciado la batería por completo');
            $table->integer('number_battery_full_charges')
                ->nullable()
                ->default(0)
                ->comment('Número de veces que se ha cargado la batería por completo');
            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de lectura');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            // ── Agregado por elemento, no por dispositivo ────────────────────
            // Sin `hardware_energy_id` se sumaban en la misma fila el panel y el
            // router, que es sumar peras con manzanas.
            $table->foreignId('hardware_energy_id')
                ->nullable()
                ->constrained('hardware_energy')->nullOnDelete()
                ->comment('Elemento al que corresponde el resumen. Sin esto se suman el panel y el router.');
            $table->decimal('energy_wh', 16, 4)
                ->nullable()
                ->comment('Vatios-hora del periodo. Esto SÍ se suma.');
            $table->decimal('energy_ah', 14, 4)
                ->nullable()
                ->comment('Amperios-hora del periodo. Esto SÍ se suma.');
            $table->unsignedInteger('readings_count')
                ->default(0)
                ->comment('Lecturas no sospechosas que entran en el resumen.');
            $table->index('hardware_energy_id', 'hardware_power_generators_historical_energy_idx');

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
