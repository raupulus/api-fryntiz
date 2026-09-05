<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwarePowerLoadsTable
 *
 * Consumo de energía de todos los dispositivos.
 * Se usa para almacenar el consumo de energía de un dispositivo.
 */
class CreateHardwarePowerLoadsTable extends Migration
{
    private $tableName = 'hardware_power_loads';

    private $tableComment = 'Almacena el consumo de energía de un dispositivo';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de $la tabla');
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
            $table->integer('fan')
                ->nullable()
                ->comment('Velocidad del ventilador en RPM, EJ:3812. Será null si no hay ventilador');
            $table->decimal('temperature', 6, 3)
                ->nullable()
                ->comment('Temperatura del dispositivo en grados centígrados, EJ:38.31.');
            // decimal y no double: en una tabla de la que se suman millones de
            // filas, el error del binario flotante se acumula.
            $table->decimal('voltage', 10, 3)
                ->nullable()
                ->comment('Tensión del periodo (V). Crudo.');
            $table->decimal('amperage', 10, 3)
                ->nullable()
                ->comment('Corriente MEDIA del periodo (A). Crudo: no se recalcula ni se tira.');
            $table->decimal('power', 12, 3)
                ->nullable()
                ->comment('V*A. Potencia MEDIA del periodo, no instantánea.');
            $table->decimal('battery_voltage', 8, 3)
                ->nullable()
                ->comment('Voltaje de la batería. Será 0 por defecto');
            $table->integer('battery_percentage')
                ->nullable()
                ->comment('Porcentaje de carga en la batería (0-100)');
            $table->timestamp('read_at')
                ->nullable()
                ->comment('Fecha y hora de lectura');

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
