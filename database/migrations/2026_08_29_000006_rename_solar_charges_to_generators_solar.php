<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `solar_charges` pasa a ser `hardware_power_generators_solar` (D109).
 *
 * Un controlador solar **es un generador**, con dos bloques que un generador
 * genérico no tiene: las **estadísticas del día** y el **acumulado histórico**
 * del mapa Modbus del Renogy Rover. Hoy se pierden enteros porque no hay
 * columnas donde guardarlos, y son justo los que pediste:
 *
 * > «quiero subir siempre todos los acumulados históricos que recibo por serial
 * >  también»
 *
 * Como el modelo pasa a ser `HardwarePowerGeneratorSolar extends
 * HardwarePowerGenerator`, la tabla tiene que ser un **superconjunto** de la de
 * generadores, o la herencia es mentira y los scopes heredados petan. De ahí los
 * tres movimientos:
 *
 *  1. **`pv_*` se renombra a `voltage` / `amperage` / `power`.** Es lo mismo que
 *     mide un generador cualquiera —lo que sale del panel—, sólo que con el
 *     nombre del fabricante. Un nombre por dato.
 *  2. **Entran las columnas comunes de lectura**: `hardware_energy_id`, los
 *     crudos, los derivados, la trazabilidad y las marcas de sospecha.
 *  3. **Se van los duplicados** (M5): `energy_voltage`, `energy_amperage` y
 *     `energy_power` medían lo mismo que `pv_*`, y `battery_soc` lo mismo que
 *     `battery_percentage` (registro Modbus 0100H).
 *
 * `load_*` se queda: la salida de consumo del propio controlador es suya y no
 * tiene equivalente en la tabla de generadores.
 *
 * La tabla no está en producción, así que no hay datos que arrastrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('solar_charges')) {
            return;
        }

        Schema::rename('solar_charges', 'hardware_power_generators_solar');

        // Primero fuera los duplicados: liberan los nombres `voltage`,
        // `amperage` y `power` para el renombrado que viene después.
        Schema::table('hardware_power_generators_solar', function (Blueprint $table) {
            foreach (['energy_voltage', 'energy_amperage', 'energy_power', 'battery_soc'] as $column) {
                if (Schema::hasColumn('hardware_power_generators_solar', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('hardware_power_generators_solar', function (Blueprint $table) {
            $table->renameColumn('pv_voltage', 'voltage');
            $table->renameColumn('pv_current', 'amperage');
            $table->renameColumn('pv_power', 'power');
        });

        Schema::table('hardware_power_generators_solar', function (Blueprint $table) {
            // ── Lo común a toda lectura de energía ────────────────────────────
            $table->foreignId('hardware_energy_id')
                ->nullable()->after('hardware_device_id')
                ->constrained('hardware_energy')->nullOnDelete()
                ->comment('Elemento generador al que corresponde la lectura.');

            $table->decimal('voltage', 10, 3)->nullable()
                ->comment('Tensión que entrega el panel (V). Crudo.')->change();
            $table->decimal('amperage', 10, 3)->nullable()
                ->comment('Corriente MEDIA del periodo que entrega el panel (A). Crudo.')->change();
            $table->decimal('power', 12, 3)->nullable()
                ->comment('V*A. Potencia MEDIA del periodo, no instantánea.')->change();

            $table->unsignedInteger('delta_seconds')->nullable()
                ->comment('Segundos que cubre la media. Sin esto, A y V no dan energía.');
            $table->decimal('energy_wh', 14, 4)->nullable()
                ->comment('V*A*s/3600. Esto SÍ se suma entre lecturas.');
            $table->decimal('energy_ah', 14, 4)->nullable()
                ->comment('A*s/3600. Esto SÍ se suma entre lecturas.');
            $table->string('energy_source', 16)->default('derived')
                ->comment('device = lo dio el aparato | derived = lo calculamos.');
            $table->string('voltage_source', 16)->default('measured')
                ->comment('measured = tensión medida | nominal = la del elemento.');
            $table->boolean('is_suspicious')->default(false)
                ->comment('Queda fuera de los agregados del día, pero se conserva.');
            $table->string('suspicious_reason', 255)->nullable();

            // ── Lo que informa el controlador y no se deduce del signo (D110) ─
            $table->integer('charging_status')->nullable()
                ->comment('Modo de carga, código del fabricante (1,2,3…).');
            $table->string('charging_status_label', 255)->nullable()
                ->comment('deactivated, activated, mppt, equalizing, boost, floating, current limiting.');

            $table->decimal('battery_temperature', 6, 2)->nullable()
                ->comment('Temperatura de la batería (°C). La del controlador es `temperature`.');
            $table->boolean('light_status')->nullable()
                ->comment('Farola encendida (0|1).');
            $table->integer('light_brightness')->nullable()
                ->comment('Brillo de la farola (0-100 %).');

            // ── Estadísticas del día que da el Rover ──────────────────────────
            $table->decimal('day_battery_voltage_min', 8, 2)->nullable();
            $table->decimal('day_battery_voltage_max', 8, 2)->nullable();
            $table->decimal('day_charging_current_max', 8, 2)->nullable();
            $table->decimal('day_discharging_current_max', 8, 2)->nullable();
            $table->decimal('day_charging_power_max', 10, 2)->nullable();
            $table->decimal('day_discharging_power_max', 10, 2)->nullable();
            $table->decimal('day_charging_amp_hours', 10, 2)->nullable();
            $table->decimal('day_discharging_amp_hours', 10, 2)->nullable();
            $table->decimal('day_power_generation_wh', 12, 2)->nullable();
            $table->decimal('day_power_consumption_wh', 12, 2)->nullable();

            // ── Acumulado desde el último reinicio del controlador ────────────
            $table->unsignedInteger('total_operating_days')->nullable()
                ->comment('Días de funcionamiento. Si BAJA, el controlador se ha reseteado.');
            $table->unsignedInteger('total_battery_over_discharges')->nullable();
            $table->unsignedInteger('total_battery_full_charges')->nullable();
            $table->decimal('total_charging_amp_hours', 14, 2)->nullable();
            $table->decimal('total_discharging_amp_hours', 14, 2)->nullable();
            $table->decimal('total_power_generation_wh', 16, 2)->nullable();
            $table->decimal('total_power_consumption_wh', 16, 2)->nullable();

            // ── Configuración que declara el controlador ──────────────────────
            $table->decimal('system_voltage', 8, 2)->nullable()
                ->comment('Tensión del sistema que declara el controlador (V).');
            $table->decimal('system_intensity', 8, 2)->nullable()
                ->comment('Intensidad máxima que declara el controlador (A).');
            $table->unsignedInteger('nominal_battery_capacity')->nullable()
                ->comment('Capacidad nominal de la batería configurada en el controlador (Ah).');
            $table->integer('load_fan')->nullable()
                ->comment('Ventilador de la salida de consumo (RPM).');

            $table->index(['hardware_energy_id', 'read_at'], 'hpg_solar_energy_read_idx');
            $table->index('total_operating_days', 'hpg_solar_operating_days_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hardware_power_generators_solar')) {
            return;
        }

        Schema::table('hardware_power_generators_solar', function (Blueprint $table) {
            $table->dropIndex('hpg_solar_energy_read_idx');
            $table->dropIndex('hpg_solar_operating_days_idx');
            $table->dropConstrainedForeignId('hardware_energy_id');
            $table->dropColumn([
                'delta_seconds', 'energy_wh', 'energy_ah',
                'energy_source', 'voltage_source',
                'is_suspicious', 'suspicious_reason',
                'charging_status', 'charging_status_label',
                'battery_temperature', 'light_status', 'light_brightness',
                'day_battery_voltage_min', 'day_battery_voltage_max',
                'day_charging_current_max', 'day_discharging_current_max',
                'day_charging_power_max', 'day_discharging_power_max',
                'day_charging_amp_hours', 'day_discharging_amp_hours',
                'day_power_generation_wh', 'day_power_consumption_wh',
                'total_operating_days', 'total_battery_over_discharges',
                'total_battery_full_charges', 'total_charging_amp_hours',
                'total_discharging_amp_hours', 'total_power_generation_wh',
                'total_power_consumption_wh',
                'system_voltage', 'system_intensity', 'nominal_battery_capacity',
                'load_fan',
            ]);
        });

        Schema::table('hardware_power_generators_solar', function (Blueprint $table) {
            $table->renameColumn('voltage', 'pv_voltage');
            $table->renameColumn('amperage', 'pv_current');
            $table->renameColumn('power', 'pv_power');
        });

        Schema::table('hardware_power_generators_solar', function (Blueprint $table) {
            $table->decimal('pv_voltage', 8, 2)->nullable()->change();
            $table->decimal('pv_current', 8, 2)->nullable()->change();
            $table->decimal('pv_power', 8, 2)->nullable()->change();

            $table->decimal('energy_voltage', 8, 2)->nullable();
            $table->decimal('energy_amperage', 8, 2)->nullable();
            $table->decimal('energy_power', 8, 2)->nullable();
            $table->smallInteger('battery_soc')->nullable();
        });

        Schema::rename('hardware_power_generators_solar', 'solar_charges');
    }
};
