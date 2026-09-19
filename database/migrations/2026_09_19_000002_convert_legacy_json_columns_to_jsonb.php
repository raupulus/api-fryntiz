<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte columnas de tipo legacy `json` a `jsonb` nativo en PostgreSQL.
 *
 * En PostgreSQL, las columnas `json` almacenan texto plano sin operadores de igualdad,
 * lo que provoca fallos `SQLSTATE[42883]: Undefined function: 7 ERROR: could not identify an equality operator for type json`
 * al realizar consultas con `DISTINCT`, `GROUP BY` o joins de relaciones polimórficas en Filament.
 * El tipo `jsonb` almacena el formato binario descompuesto, optimiza el rendimiento y admite ordenación,
 * comparaciones e índices GIN.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table): void {
            $table->jsonb('extra')
                ->nullable()
                ->comment('Métricas de estado adicionales del dispositivo en formato JSONB (RAM, procesos, etc.).')
                ->change();
        });

        Schema::table('newsletter', function (Blueprint $table): void {
            $table->jsonb('preferences')
                ->nullable()
                ->comment('Qué contenidos quiere recibir en formato JSONB.')
                ->change();

            $table->jsonb('metadata')
                ->nullable()
                ->comment('Datos adicionales de la suscripción (origen, campaña…) en formato JSONB.')
                ->change();
        });

        Schema::table('emails', function (Blueprint $table): void {
            $table->jsonb('client_accept_language')
                ->nullable()
                ->comment('Idiomas aceptados por el cliente en formato JSONB.')
                ->change();

            $table->jsonb('attributes')
                ->nullable()
                ->comment('Atributos y metadatos adicionales del correo en formato JSONB.')
                ->change();
        });

        Schema::table('meteorology_aemet_adverse_events', function (Blueprint $table): void {
            $table->jsonb('polygons')
                ->nullable()
                ->comment('Polígonos geoespaciales de la zona afectada en formato JSONB.')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('hardware_devices', function (Blueprint $table): void {
            $table->json('extra')
                ->nullable()
                ->comment('Métricas de estado adicionales del dispositivo en formato JSON (RAM, procesos, etc.).')
                ->change();
        });

        Schema::table('newsletter', function (Blueprint $table): void {
            $table->json('preferences')
                ->nullable()
                ->comment('Qué contenidos quiere recibir.')
                ->change();

            $table->json('metadata')
                ->nullable()
                ->comment('Datos adicionales de la suscripción (origen, campaña…).')
                ->change();
        });

        Schema::table('emails', function (Blueprint $table): void {
            $table->json('client_accept_language')
                ->nullable()
                ->comment('Idiomas aceptados por el cliente.')
                ->change();

            $table->json('attributes')
                ->nullable()
                ->comment('Atributos y metadatos adicionales del correo.')
                ->change();
        });

        Schema::table('meteorology_aemet_adverse_events', function (Blueprint $table): void {
            $table->json('polygons')
                ->nullable()
                ->comment('Polígonos geoespaciales de la zona afectada.')
                ->change();
        });
    }
};
