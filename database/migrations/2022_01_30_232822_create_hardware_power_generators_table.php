<?php

declare(strict_types=1);

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
            $table->comment('Carga de energía generada por dispositivo de hardware en el momento.');
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
            $table->decimal('battery_voltage', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Voltaje de la batería');
            $table->decimal('battery_temperature', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura de la batería en su exterior (sensor externo a la batería)');
            $table->integer('battery_percentage')
                ->nullable()
                ->default(0)
                ->comment('Porcentaje de carga en la batería (0-100)');
            $table->integer('charging_status')
                ->nullable()
                ->default(0)
                ->comment('El modo de cargar batería en el momento, código interno del fabricante (1,2,3...).');
            $table->string('charging_status_label', 255)
                ->nullable()
                ->default('deactivated')
                ->comment('El modo de cargar batería en el momento. (deactivated, activated, mppt, equalizing, boost, floating, current limiting)');
            // Sin `default(0)`: «no tengo dato» no es cero, y un cero por
            // defecto se cuela en las medias como si fuera una medición real.
            $table->decimal('amperage', 10, 3)
                ->nullable()
                ->comment('Corriente MEDIA del periodo (A). Crudo: no se recalcula ni se tira.');
            $table->decimal('voltage', 10, 3)
                ->nullable()
                ->comment('Tensión del periodo (V). Crudo.');
            $table->decimal('power', 12, 3)
                ->nullable()
                ->comment('V*A. Potencia MEDIA del periodo, no instantánea.');
            $table->boolean('light_status')
                ->nullable()
                ->default(false)
                ->comment('Indica si hay luz de calle mediante booleano 0|1.');
            $table->integer('light_brightness')
                ->nullable()
                ->default(0)
                ->comment('Devuelve el porcentaje brillo de la luz de calle (0-100%).');

            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de lectura');

            $table->decimal('temperature', 6, 3)
                ->nullable()
                ->comment('Temperatura del aparato (°C). La de la batería es `battery_temperature`.');

            // ── Crudos y derivados de la lectura (fase de energía) ───────────
            $table->foreignId('hardware_energy_id')
                ->nullable()
                ->constrained('hardware_energy')->nullOnDelete()
                ->comment('Elemento concreto al que corresponde la lectura.');
            $table->unsignedInteger('delta_seconds')
                ->nullable()
                ->comment('Segundos que cubre la media. Sin esto, A y V no dan energía.');
            $table->decimal('energy_wh', 14, 4)
                ->nullable()
                ->comment('V*A*s/3600. Esto SÍ se suma entre lecturas.');
            $table->decimal('energy_ah', 14, 4)
                ->nullable()
                ->comment('A*s/3600. Esto SÍ se suma entre lecturas.');
            $table->string('energy_source', 16)
                ->default('derived')
                ->comment('device = lo dio el aparato | derived = lo calculamos.');
            $table->string('voltage_source', 16)
                ->default('measured')
                ->comment('measured = tensión medida | nominal = la del elemento.');
            $table->boolean('is_suspicious')
                ->default(false)
                ->comment('Queda fuera de los agregados del día, pero se conserva.');
            $table->string('suspicious_reason', 255)->nullable();

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');

            $table->index(['hardware_energy_id', 'read_at']);
            $table->index(['hardware_device_id', 'read_at']);
            $table->index(['is_suspicious', 'read_at']);
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
