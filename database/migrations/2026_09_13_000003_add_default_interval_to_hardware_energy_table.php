<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El intervalo que se supone cuando el aparato no manda `duration`.
 *
 * `duration` es lo que convierte una potencia en energía: `Wh = V · A · s/3600`.
 * Cuando no llega, el servidor asumía **60 segundos fijos para todo el mundo**,
 * y eso es una mentira distinta en cada instalación: un nodo que sube cada diez
 * minutos registraba la sexta parte de la energía real, y en silencio.
 *
 * El valor correcto no es global, es de cada elemento: depende de cada cuánto
 * sube ese cacharro. Por eso vive aquí, con 60 de partida para no cambiar el
 * comportamiento de nada que ya exista, y editable desde el panel.
 *
 * Lo ideal sigue siendo que el aparato mande `duration` en cada subida: sólo él
 * sabe cuántos segundos pasaron de verdad cuando hubo un corte de red. Esto es
 * el respaldo para cuando no puede.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy';

    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->unsignedInteger('default_interval_seconds')
                ->default(60)
                ->after('auto_calculate_history')
                ->comment('Segundos que se suponen entre lecturas cuando la subida no trae `duration`.');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn('default_interval_seconds');
        });
    }
};
