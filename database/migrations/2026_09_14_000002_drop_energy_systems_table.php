<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quita `energy_systems`, la «instalación» que agrupaba elementos (D79).
 *
 * Se quedó sin uso cuando `2026_09_12_000004_update_hardware_energy_table`
 * soltó `hardware_energy.energy_system_id`: desde entonces ningún modelo,
 * recurso, policy ni endpoint la lee. El agrupamiento que resolvía lo hace hoy
 * el propio aparato —un dispositivo con su generador, su batería y sus
 * consumos—, y sus dos filas eran los dos controladores solares, que ya están
 * como aparatos.
 *
 * La migración que la crea se queda: la de `hardware_energy` de 2022 le pone una
 * FK, y sin ella una instalación nueva no migra. Sale cuando se consoliden las
 * migraciones de energía (`docs/future/limpieza-energia-legacy.md`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('energy_systems');
    }

    public function down(): void
    {
        Schema::create('energy_systems', function (Blueprint $table) {
            $table->comment('Instalación energética: agrupa elementos que comparten batería y tensión.');
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->boolean('is_standalone')->default(false);
            $table->decimal('nominal_voltage', 8, 2)->nullable();
            $table->decimal('battery_capacity_ah', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_standalone']);
        });
    }
};
