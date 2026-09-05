<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `solar_charges` para almacenar lecturas de cargadores solares.
 * Registra voltaje, corriente y potencia de batería, panel fotovoltaico y carga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_power_generators_solar', function (Blueprint $table) {
            $table->comment('Lecturas del regulador de carga solar: generación, batería y consumo, con los acumulados que declara el propio controlador.');
            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('hardware_device_id')
                ->comment('Dispositivo hardware asociado al cargador solar');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->date('date')->nullable()->comment('Fecha de la lectura');
            $table->timestamp('read_at')->nullable()->comment('Timestamp exacto de la lectura');

            $table->string('hardware', 255)->nullable()->comment('Nombre o modelo del hardware del cargador');
            $table->string('version', 255)->nullable()->comment('Versión del firmware o software');
            $table->string('serial_number', 255)->nullable()->comment('Número de serie del dispositivo');
            $table->string('battery_type', 255)->nullable()->comment('Tipo de batería (LiFePO4, AGM, etc.)');

            $table->decimal('battery_voltage', 8, 2)->nullable()->comment('Voltaje de la batería en voltios');
            $table->decimal('battery_current', 8, 2)->nullable()->comment('Corriente de la batería en amperios');
            $table->decimal('battery_power', 8, 2)->nullable()->comment('Potencia de la batería en vatios');
            $table->smallInteger('battery_percentage')->nullable()->comment('Porcentaje de batería (0-100)');

            $table->decimal('voltage', 10, 3)->nullable()->comment('Tensión que entrega el panel (V). Crudo.');
            $table->decimal('amperage', 10, 3)->nullable()->comment('Corriente MEDIA del periodo que entrega el panel (A). Crudo.');
            $table->decimal('power', 12, 3)->nullable()->comment('V*A. Potencia MEDIA del periodo, no instantánea.');

            $table->decimal('load_voltage', 8, 2)->nullable()->comment('Voltaje de la carga en voltios');
            $table->decimal('load_current', 8, 2)->nullable()->comment('Corriente de la carga en amperios (load_amperage)');
            $table->decimal('load_power', 8, 2)->nullable()->comment('Potencia de la carga en vatios');

            $table->decimal('temperature', 5, 2)->nullable()->comment('Temperatura del controlador en grados Celsius');

            $table->timestamps();

            $table->index(['hardware_device_id', 'created_at'], 'hardware_power_generators_solar_device_created_idx');

            // ── Lo común a toda lectura de energía ────────────────────────────
            $table->foreignId('hardware_energy_id')
                ->nullable()
                ->comment('Elemento generador al que corresponde la lectura.')
                ->constrained('hardware_energy')->nullOnDelete();

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
            $table->string('suspicious_reason', 255)->nullable()
                ->comment('Por qué se marcó como sospechosa. Null si no lo es.');

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
            $table->decimal('day_battery_voltage_min', 8, 2)->nullable()
                ->comment('Tensión mínima de la batería en el día (V).');
            $table->decimal('day_battery_voltage_max', 8, 2)->nullable()
                ->comment('Tensión máxima de la batería en el día (V).');
            $table->decimal('day_charging_current_max', 8, 2)->nullable()
                ->comment('Corriente máxima de carga en el día (A).');
            $table->decimal('day_discharging_current_max', 8, 2)->nullable()
                ->comment('Corriente máxima de descarga en el día (A).');
            $table->decimal('day_charging_power_max', 10, 2)->nullable()
                ->comment('Potencia máxima de carga en el día (W).');
            $table->decimal('day_discharging_power_max', 10, 2)->nullable()
                ->comment('Potencia máxima de descarga en el día (W).');
            $table->decimal('day_charging_amp_hours', 10, 2)->nullable()
                ->comment('Amperios-hora cargados en el día (Ah).');
            $table->decimal('day_discharging_amp_hours', 10, 2)->nullable()
                ->comment('Amperios-hora descargados en el día (Ah).');
            $table->decimal('day_power_generation_wh', 12, 2)->nullable()
                ->comment('Energía generada en el día (Wh).');
            $table->decimal('day_power_consumption_wh', 12, 2)->nullable()
                ->comment('Energía consumida en el día (Wh).');

            // ── Acumulado desde el último reinicio del controlador ────────────
            $table->unsignedInteger('total_operating_days')->nullable()
                ->comment('Días de funcionamiento. Si BAJA, el controlador se ha reseteado.');
            $table->unsignedInteger('total_battery_over_discharges')->nullable()
                ->comment('Veces que la batería se ha sobredescargado desde el último reinicio.');
            $table->unsignedInteger('total_battery_full_charges')->nullable()
                ->comment('Veces que la batería ha llegado a carga completa desde el último reinicio.');
            $table->decimal('total_charging_amp_hours', 14, 2)->nullable()
                ->comment('Amperios-hora cargados en total (Ah).');
            $table->decimal('total_discharging_amp_hours', 14, 2)->nullable()
                ->comment('Amperios-hora descargados en total (Ah).');
            $table->decimal('total_power_generation_wh', 16, 2)->nullable()
                ->comment('Energía generada en total (Wh).');
            $table->decimal('total_power_consumption_wh', 16, 2)->nullable()
                ->comment('Energía consumida en total (Wh).');

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
        Schema::dropIfExists('hardware_power_generators_solar');
    }
};
