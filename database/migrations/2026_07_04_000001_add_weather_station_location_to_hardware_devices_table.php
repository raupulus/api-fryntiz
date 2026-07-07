<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade a los dispositivos de hardware su ubicación física (interior/exterior)
 * y una zona opcional.
 *
 * `location_type` aplica a CUALQUIER hardware (por defecto interior); no marca si
 * es estación meteorológica. Que un dispositivo sea estación se determina por su
 * tipo de hardware ("Estación Meteorológica"). La zona permite agrupar equipos.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_devices';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('location_type', 20)
                ->default('indoor')
                ->after('name_friendly')
                ->comment('Ubicación física del hardware: indoor (interior) u outdoor (exterior). Por defecto interior.');

            $table->string('zone', 100)
                ->nullable()
                ->after('location_type')
                ->comment('Zona/ubicación concreta del hardware, EJ: Azotea, Salón, Jardín.');

            // Patrón de consulta: filtrar/agrupar por tipo de ubicación.
            $table->index('location_type');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropIndex(['location_type']);
            $table->dropColumn(['location_type', 'zone']);
        });
    }
};
