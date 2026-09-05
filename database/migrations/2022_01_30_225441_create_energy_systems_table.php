<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La instalación (D79).
 *
 * Agrupa elementos para poder preguntar «cuánto ha generado la casa hoy» sin
 * tener que listar ids a mano. Hoy no existe nada equivalente: las lecturas
 * cuelgan del dispositivo que mide, y un mismo monitor mide cosas de
 * instalaciones distintas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_systems', function (Blueprint $table) {
            $table->comment('Instalación energética: agrupa elementos que comparten batería y tensión.');
            $table->id();

            $table->foreignId('user_id')
                ->comment('Propietario de la instalación.')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 255)->comment('«Casa 24V», «Nodo UV», «Banco de routers».');
            $table->string('slug', 255)->unique()
                ->comment('Identificador estable para la API, sin acentos ni espacios.');

            $table->boolean('is_standalone')
                ->default(false)
                ->comment('Nodo autoabastecido: placa pequeña + batería, sin red.');

            $table->decimal('nominal_voltage', 8, 2)
                ->nullable()
                ->comment('Tensión nominal de la instalación (V).');

            $table->decimal('battery_capacity_ah', 10, 2)
                ->nullable()
                ->comment('Capacidad del banco de baterías (Ah).');

            $table->text('notes')->nullable()
                ->comment('Anotaciones libres sobre la instalación.');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_standalone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_systems');
    }
};
