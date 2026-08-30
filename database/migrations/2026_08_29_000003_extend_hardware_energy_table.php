<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `hardware_energy` era ya el «elemento energético», sólo que sin los campos
 * que lo hacen útil (D81).
 *
 * Es la entidad que faltaba y que llevaba ahí desde 2022. Un monitor mide un
 * panel y un router a la vez; el panel no existía como fila en ningún sitio, así
 * que no había dónde guardar su tensión ni su tipo. Sin tensión por elemento,
 * los vatios se calculan multiplicando la corriente de cada canal por **el único
 * voltaje que trae la petición**: un panel de 24 V y una Pico de 3,7 V en la
 * misma petición dan números sin sentido.
 *
 * `is_generator` se rellena desde `role` y **no se borra todavía** (D70):
 * primero se migran los datos, después se quita la columna vieja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_energy', function (Blueprint $table) {
            $table->foreignId('energy_system_id')
                ->nullable()->after('hardware_device_monitorized_id')
                ->constrained('energy_systems')->nullOnDelete()
                ->comment('Instalación a la que pertenece el elemento.');

            $table->foreignId('energy_source_type_id')
                ->nullable()->after('energy_system_id')
                ->constrained('energy_source_types')->nullOnDelete()
                ->comment('Tipo de fuente: solar, eólica, red…');

            $table->string('name', 255)->nullable()->after('energy_source_type_id')
                ->comment('«Panel sur», «Router principal».');

            $table->string('role', 16)->default('load')->after('name')
                ->comment('generator | load | storage.');

            // El campo que arregla el cálculo de los vatios.
            $table->decimal('nominal_voltage', 8, 2)->nullable()->after('role')
                ->comment('Tensión nominal del elemento (V). Se usa si la medida no es plausible.');

            $table->decimal('voltage_min', 8, 2)->nullable()->after('nominal_voltage')
                ->comment('Por debajo de esto, la tensión medida se considera errónea.');

            $table->decimal('voltage_max', 8, 2)->nullable()->after('voltage_min')
                ->comment('Por encima de esto, la tensión medida se considera errónea.');

            $table->decimal('rated_power_w', 10, 2)->nullable()->after('voltage_max')
                ->comment('Potencia nominal (W).');

            $table->decimal('capacity_mah', 12, 2)->nullable()->after('rated_power_w')
                ->comment('Capacidad de la batería del elemento (mAh).');

            $table->decimal('capacity_wh', 12, 2)->nullable()->after('capacity_mah')
                ->comment('Capacidad de la batería del elemento (Wh).');

            $table->boolean('is_active')->default(true)->after('capacity_wh')
                ->comment('Un elemento retirado deja de aceptar lecturas nuevas.');

            $table->index(['energy_system_id', 'role']);
            $table->index(['hardware_device_id', 'sensor_position']);
        });

        // `role` se deriva de la columna que ya había.
        DB::table('hardware_energy')->where('is_generator', true)->update(['role' => 'generator']);
        DB::table('hardware_energy')->where('is_generator', false)->update(['role' => 'load']);
    }

    public function down(): void
    {
        Schema::table('hardware_energy', function (Blueprint $table) {
            $table->dropIndex(['energy_system_id', 'role']);
            $table->dropIndex(['hardware_device_id', 'sensor_position']);
            $table->dropConstrainedForeignId('energy_system_id');
            $table->dropConstrainedForeignId('energy_source_type_id');
            $table->dropColumn([
                'name', 'role', 'nominal_voltage', 'voltage_min', 'voltage_max',
                'rated_power_w', 'capacity_mah', 'capacity_wh', 'is_active',
            ]);
        });
    }
};
