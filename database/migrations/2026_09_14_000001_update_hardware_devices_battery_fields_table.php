<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reordena los campos de batería de `hardware_devices`.
 *
 * `battery_percentage` y `battery_read_at` (D108) nunca llegaron a validarse
 * en `DeviceStatusPayload`, ni a mostrarse en el panel, ni a exponerse en la
 * API: ni un solo test las ejercitaba. Se quitan sin dejar rastro.
 *
 * En su lugar se añade `battery_nominal_voltage`: la tensión de diseño que
 * declara el fabricante (no una medida), editable desde el panel junto a
 * `battery_nominal_capacity`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table) {
            $table->decimal('battery_nominal_voltage', 8, 3)
                ->nullable()
                ->after('battery_nominal_capacity')
                ->comment('Tensión nominal de diseño de la batería (V), EJ: 12.');

            $table->dropColumn(['battery_percentage', 'battery_read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table) {
            $table->dropColumn('battery_nominal_voltage');

            $table->unsignedTinyInteger('battery_percentage')
                ->nullable()
                ->comment('Carga de la batería del propio dispositivo (%).');
            $table->timestamp('battery_read_at')
                ->nullable()
                ->comment('Cuándo se midió. Sin esto no se distingue un dato de ahora de uno de hace semanas.');
        });
    }
};
