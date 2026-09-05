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
                ->comment('Dispositivo del que procede la lectura.');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->foreignId('hardware_energy_id')
                ->nullable()
                ->comment('Elemento concreto al que corresponde la lectura.')
                ->constrained('hardware_energy')->nullOnDelete();
            $table->decimal('battery_voltage', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Tensión de la batería (V).');
            $table->decimal('battery_temperature', 10, 2)
                ->nullable()
                ->default(0)
                ->comment('Temperatura de la batería, medida por un sensor externo a ella (°C).');
            $table->integer('battery_percentage')
                ->nullable()
                ->default(0)
                ->comment('Estado de carga de la batería (0-100 %).');
            $table->integer('charging_status')
                ->nullable()
                ->default(0)
                ->comment('Modo de carga en curso, con el código del fabricante (1, 2, 3…).');
            $table->string('charging_status_label', 255)
                ->nullable()
                ->default('deactivated')
                ->comment('Modo de carga en curso: deactivated, activated, mppt, equalizing, boost, floating o current limiting.');
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
                ->comment('Si el controlador detecta luz solar (usa el panel como sensor).');
            $table->integer('light_brightness')
                ->nullable()
                ->default(0)
                ->comment('Intensidad de la luz solar detectada (0-100 %).');

            $table->timestamp('read_at')
                ->nullable()
                ->comment('Momento en que se tomó la lectura.');

            $table->decimal('temperature', 6, 3)
                ->nullable()
                ->comment('Temperatura del aparato (°C). La de la batería es `battery_temperature`.');

            // ── Crudos y derivados de la lectura (fase de energía) ───────────
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
            $table->string('suspicious_reason', 255)->nullable()
                ->comment('Por qué se marcó como sospechosa. Null si no lo es.');

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
