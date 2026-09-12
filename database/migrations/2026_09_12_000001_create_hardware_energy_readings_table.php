<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla unificada de lecturas instantáneas y de intervalo de energía.
 *
 * Unifica las tablas `hardware_power_generators`, `hardware_power_loads` y
 * `hardware_power_generators_solar`. La marca temporal es el propio `created_at`
 * del servidor al recibir la lectura (sin columna `read_at`).
 *
 * // TODO: Las tablas legacy se conservan en frío como backup de seguridad
 * // y se eliminarán tras verificar la estabilidad en producción.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy_readings';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Lecturas instantáneas o de intervalo de energía (generación, consumo y batería)');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('hardware_device_id')
                ->comment('Dispositivo físico que tomó o al que pertenece la lectura');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedBigInteger('hardware_energy_id')
                ->nullable()
                ->comment('Elemento concreto (canal/rol) al que corresponde la lectura');
            $table->foreign('hardware_energy_id')
                ->references('id')->on('hardware_energy')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->decimal('voltage', 10, 3)
                ->nullable()
                ->comment('Tensión del periodo (V). Crudo.');
            $table->decimal('amperage', 10, 3)
                ->nullable()
                ->comment('Corriente MEDIA del periodo (A). Crudo.');
            $table->decimal('power', 12, 3)
                ->nullable()
                ->comment('Potencia MEDIA del periodo (W). Crudo o derivado V*A.');

            $table->unsignedInteger('delta_seconds')
                ->nullable()
                ->comment('Segundos que cubre la media (duración de la muestra).');

            $table->decimal('energy_wh', 14, 4)
                ->nullable()
                ->comment('Vatios-hora calculados para este intervalo (W*s/3600).');
            $table->decimal('energy_ah', 14, 4)
                ->nullable()
                ->comment('Amperios-hora calculados para este intervalo (A*s/3600).');

            $table->string('energy_source', 16)
                ->default('derived')
                ->comment('device = reportado por hardware | derived = calculado.');
            $table->string('voltage_source', 16)
                ->default('measured')
                ->comment('measured = tensión medida | nominal = fallback.');

            $table->decimal('battery_voltage', 10, 2)
                ->nullable()
                ->comment('Tensión de la batería si el elemento o dispositivo la monitoriza (V).');
            $table->smallInteger('battery_percentage')
                ->nullable()
                ->comment('Estado de carga de la batería (0-100 %).');

            $table->decimal('temperature', 6, 3)
                ->nullable()
                ->comment('Temperatura en °C (del elemento medido, disipador o batería).');
            $table->smallInteger('fan')
                ->nullable()
                ->comment('Estado o velocidad del ventilador de refrigeración.');

            $table->smallInteger('charging_status')
                ->nullable()
                ->comment('Código de modo de carga (MPPT, float, boost, etc.).');
            $table->string('charging_status_label', 255)
                ->nullable()
                ->comment('Etiqueta textual del modo de carga.');

            $table->boolean('light_status')
                ->nullable()
                ->comment('Si se detecta iluminación/luz solar (en farolas solares).');
            $table->smallInteger('light_brightness')
                ->nullable()
                ->comment('Intensidad de luz solar o farola (0-100 %).');

            $table->boolean('is_suspicious')
                ->default(false)
                ->comment('true = lectura sospechosa/anómala, excluida de agregados.');
            $table->string('suspicious_reason', 255)
                ->nullable()
                ->comment('Motivo por el que se marcó sospechosa.');

            $table->timestamps();

            $table->index(['hardware_energy_id', 'created_at']);
            $table->index(['hardware_device_id', 'created_at']);
            $table->index(['is_suspicious', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
