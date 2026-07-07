<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade a los dispositivos de hardware el último estado conocido del propio
 * dispositivo (telemetría de estado): temperatura, tensión, nivel de batería,
 * uso de CPU/disco, tiempo de actividad y métricas extra.
 *
 * No se guarda histórico: estas columnas reflejan siempre el último estado
 * recibido. Las columnas `ip_local`, `ip_public` y `last_seen_at` ya existen y
 * se actualizan junto con estos datos en cada subida.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_devices';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->decimal('temp', 6, 2)
                ->nullable()
                ->after('ip_public')
                ->comment('Última temperatura conocida del dispositivo en grados Celsius.');

            $table->decimal('voltage', 8, 3)
                ->nullable()
                ->after('temp')
                ->comment('Última tensión conocida del dispositivo en voltios (EJ: batería por divisor de tensión).');

            $table->unsignedSmallInteger('battery_level')
                ->nullable()
                ->after('voltage')
                ->comment('Último nivel de batería conocido en porcentaje (0-100).');

            $table->decimal('cpu', 5, 2)
                ->nullable()
                ->after('battery_level')
                ->comment('Último uso de CPU conocido en porcentaje (0-100).');

            $table->decimal('disk', 5, 2)
                ->nullable()
                ->after('cpu')
                ->comment('Último uso de disco conocido en porcentaje (0-100).');

            $table->unsignedBigInteger('uptime')
                ->nullable()
                ->after('disk')
                ->comment('Último tiempo de actividad conocido del dispositivo en segundos.');

            $table->json('extra')
                ->nullable()
                ->after('uptime')
                ->comment('Métricas de estado adicionales del dispositivo en formato JSON (RAM, procesos, etc.).');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['temp', 'voltage', 'battery_level', 'cpu', 'disk', 'uptime', 'extra']);
        });
    }
};
