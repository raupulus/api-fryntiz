<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la columna `is_public` a la tabla `hardware_devices` para controlar
 * qué dispositivos son visibles en el catálogo y escaparate público de hardware.
 * Por defecto es false (privacidad por diseño: ningún dispositivo se hace público sin marcarlo).
 */
return new class extends Migration
{
    private string $tableName = 'hardware_devices';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->boolean('is_public')
                ->default(false)
                ->after('notify_on_silence')
                ->comment('Indica si el dispositivo es visible en el catálogo y escaparate público de hardware');

            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropIndex(['is_public']);
            $table->dropColumn('is_public');
        });
    }
};
