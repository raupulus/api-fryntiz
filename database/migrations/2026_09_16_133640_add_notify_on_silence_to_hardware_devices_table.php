<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `iot:check-silent-devices` avisaba por igual de todos los dispositivos,
 * pero hay cacharros que solo se encienden un par de veces al mes a
 * propósito: no están mudos, es que no tienen por qué reportar todos los
 * días. Esta columna permite excluirlos del aviso sin dejar de vigilar el
 * resto del parque.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_devices';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->boolean('notify_on_silence')
                ->default(true)
                ->after('last_seen_at')
                ->comment('Si "iot:check-silent-devices" debe avisar cuando este dispositivo deje de reportar. Desactivar en hardware que se enciende de forma esporádica a propósito.');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn('notify_on_silence');
        });
    }
};
