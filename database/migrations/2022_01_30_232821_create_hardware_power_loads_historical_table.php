<?php

declare(strict_types=1);

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
            $table->comment('Carga de energía consumida por dispositivo de hardware en el total.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo del que procede la lectura.');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->foreignId('hardware_energy_id')
                ->nullable()
                ->comment('Elemento al que corresponde el resumen. Sin esto se suman el panel y el router.')
                ->constrained('hardware_energy')->nullOnDelete();
            $table->integer('fan_min')
                ->nullable()
                ->comment('Velocidad mínima de ventilador (rpm)');
            $table->integer('fan_max')
                ->nullable()
                ->comment('Velocidad máxima de ventilador (rpm)');
            $table->decimal('temperature_min', 6, 3)
                ->nullable()
                ->default(0)
                ->comment('Temperatura mínima (°C)');
            $table->decimal('temperature_max', 6, 3)
                ->nullable()
                ->default(0)
                ->comment('Temperatura máxima (°C)');
            $table->decimal('voltage_min', 8, 3)
                ->nullable()
                ->default(0)
                ->comment('Volts mínimo (V)');
            $table->decimal('voltage_max', 8, 3)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo (V)');
            $table->decimal('battery_min', 8, 3)
                ->nullable()
                ->comment('Voltaje mínimo de batería (V)');
            $table->decimal('battery_max', 8, 3)
                ->nullable()
                ->comment('Voltaje máximo de batería (V)');
            $table->decimal('amperage_min', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Amperaje mínimo (A)');
            $table->decimal('amperage_max', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Amperaje máximo (A)');
            $table->decimal('power_min', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Potencia mínima (W)');
            $table->decimal('power_max', 20, 6)
                ->nullable()
                ->default(0)
                ->comment('Potencia máxima (W)');
            $table->integer('days_operating')
                ->nullable()
                ->default(0)
                ->comment('Número de días que el dispositivo ha estado operativo');
            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de la última lectura');

            // ── Agregado por elemento, no por dispositivo ────────────────────
            // Sin `hardware_energy_id` se sumaban en la misma fila el panel y el
            // router, que es sumar peras con manzanas.
            $table->decimal('energy_wh', 16, 4)
                ->nullable()
                ->comment('Vatios-hora del periodo. Esto SÍ se suma.');
            $table->decimal('energy_ah', 14, 4)
                ->nullable()
                ->comment('Amperios-hora del periodo. Esto SÍ se suma.');
            $table->unsignedInteger('readings_count')
                ->default(0)
                ->comment('Lecturas no sospechosas que entran en el resumen.');
            $table->index('hardware_energy_id', 'hardware_power_loads_historical_energy_idx');
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
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
