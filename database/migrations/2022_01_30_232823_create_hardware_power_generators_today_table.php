<?php

declare(strict_types=1);

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
            $table->comment('Carga de energía generada por dispositivo de hardware en el día.');
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
            $table->decimal('temperature_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura mínima (°C)');
            $table->decimal('temperature_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura máxima (°C)');
            $table->decimal('voltage_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Volts mínimo (V)');
            $table->decimal('voltage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo (V)');
            $table->decimal('battery_min', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje mínimo de batería (V)');
            $table->decimal('battery_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje máximo de batería (V)');
            $table->integer('battery_percentage_min')
                ->nullable()
                ->default(0)
                ->comment('Porcentaje de batería mínimo (%)');
            $table->integer('battery_percentage_max')
                ->nullable()
                ->default(0)
                ->comment('Porcentaje de batería máximo (%)');
            $table->decimal('amperage_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Máxima carga en amperios (Ah)');
            $table->decimal('power_max', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Potencia máxima (W)');
            $table->date('date')
                ->nullable()
                ->comment('Fecha de medición');
            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de la última lectura');

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            // ── Agregado por elemento, no por dispositivo ────────────────────
            // Sin `hardware_energy_id` se sumaban en la misma fila el panel y el
            // router, que es sumar peras con manzanas.
            $table->foreignId('hardware_energy_id')
                ->nullable()
                ->comment('Elemento al que corresponde el resumen. Sin esto se suman el panel y el router.')
                ->constrained('hardware_energy')->nullOnDelete();
            $table->decimal('energy_wh', 16, 4)
                ->nullable()
                ->comment('Vatios-hora del periodo. Esto SÍ se suma.');
            $table->decimal('energy_ah', 14, 4)
                ->nullable()
                ->comment('Amperios-hora del periodo. Esto SÍ se suma.');
            $table->unsignedInteger('readings_count')
                ->default(0)
                ->comment('Lecturas no sospechosas que entran en el resumen.');
            $table->decimal('amperage_min', 10, 3)
                ->nullable()
                ->comment('Corriente mínima instantánea del día (A).');
            $table->decimal('power_min', 12, 3)
                ->nullable()
                ->comment('Potencia mínima instantánea del día (W).');
            $table->index(['hardware_energy_id', 'date'], 'hardware_power_generators_today_energy_date_idx');

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
