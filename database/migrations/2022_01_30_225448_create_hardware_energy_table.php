<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwareComponentsTable
 *
 * Tabla asociando componentes concretos a los dispositivos.
 */
class CreateHardwareEnergyTable extends Migration
{
    private $tableName = 'hardware_energy';

    private $tableComment = 'Asocia dispositivos que monitorizan consumo o generación de energía con sus dispositivos monitorizados';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Asocia dispositivos que monitorizan consumo o generación de energía con sus dispositivos monitorizados');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo que hace de medidor.');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('hardware_device_monitorized_id')
                ->nullable()
                ->comment('Dispositivo cuyo consumo o generación se está midiendo.');
            $table->foreign('hardware_device_monitorized_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->foreignId('energy_source_type_id')
                ->nullable()
                ->comment('Tipo de fuente: solar, eólica, red…')
                ->constrained('energy_source_types')->nullOnDelete();
            $table->foreignId('energy_system_id')
                ->nullable()
                ->comment('Instalación a la que pertenece el elemento.')
                ->constrained('energy_systems')->nullOnDelete();

            $table->boolean('is_generator')
                ->default(false)
                ->nullable()
                ->comment('true = genera energía; false = la consume. Lo detalla `role`.');

            $table->smallInteger('sensor_position')
                ->nullable()
                ->comment('Qué sensor del medidor corresponde a este elemento, cuando tiene varios.');

            // ── Instalación energética (fase de energía) ─────────────────────
            $table->string('name', 255)
                ->nullable()
                ->comment('«Panel sur», «Router principal».');
            $table->string('role', 16)
                ->default('load')
                ->comment('generator | load | storage.');

            // La tensión nominal es lo que arregla el cálculo de los vatios
            // cuando la medida no es plausible.
            $table->decimal('nominal_voltage', 8, 2)
                ->nullable()
                ->comment('Tensión nominal del elemento (V). Se usa si la medida no es plausible.');
            $table->decimal('voltage_min', 8, 2)
                ->nullable()
                ->comment('Por debajo de esto, la tensión medida se considera errónea.');
            $table->decimal('voltage_max', 8, 2)
                ->nullable()
                ->comment('Por encima de esto, la tensión medida se considera errónea.');
            $table->decimal('rated_power_w', 10, 2)
                ->nullable()
                ->comment('Potencia nominal (W).');
            $table->decimal('capacity_mah', 12, 2)
                ->nullable()
                ->comment('Capacidad de la batería del elemento (mAh).');
            $table->decimal('capacity_wh', 12, 2)
                ->nullable()
                ->comment('Capacidad de la batería del elemento (Wh).');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Un elemento retirado deja de aceptar lecturas nuevas.');

            $table->index(['energy_system_id', 'role']);
            $table->index(['hardware_device_id', 'sensor_position']);
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');
        });

        // `role` derivado de is_generator, para cuando la tabla se monta sobre
        // datos que ya existían. La columna `role` llegó después que
        // `is_generator` y su migración derivaba una de la otra; al plegarla
        // aquí quedaba sólo el `default('load')`, así que un generador
        // heredado se habría quedado marcado como consumo.
        DB::table($this->tableName)->where('is_generator', true)->update(['role' => 'generator']);
        DB::table($this->tableName)->where('is_generator', false)->update(['role' => 'load']);

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
            $table->dropForeign(['hardware_available_component_id']);
        });
    }
}
