<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos del sistema GDACS (Global Disaster Alert and Coordination System)
 * dentro del radio de interés de la instalación (ver `config/gdacs.php`).
 *
 * Una fila por `(event_type, event_id)`: GDACS reescribe el mismo `event_id`
 * según el suceso evoluciona (nuevos episodios, cambia el nivel de alerta,
 * deja de estar activo...), así que se actualiza in-place con cada sondeo en
 * vez de acumular una fila por episodio — ver `docs/future/gdacs-api.md`.
 *
 * Sin nada de país: el filtro de la consulta ya acota a lo que interesa (por
 * defecto España, `config('gdacs.country')`), así que guardarlo aquí sería
 * repetir el mismo valor en cada fila.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdacs_events', function (Blueprint $table) {
            $table->comment('Eventos del sistema GDACS dentro del radio de interés configurado.');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->string('event_type', 2)
                ->comment('Código de desastre GDACS: EQ, TC, FL, VO, DR o WF');

            $table->unsignedBigInteger('event_id')
                ->comment('Id que asigna GDACS al suceso. Único por event_type, no globalmente');

            $table->unsignedBigInteger('episode_id')->nullable()
                ->comment('Último episodio conocido del suceso (GDACS los va numerando según evoluciona)');

            $table->string('name')->nullable()
                ->comment('Título corto del suceso tal cual lo da GDACS, ej. "Forest fires in Spain"');

            $table->string('alert_level', 6)
                ->comment('Nivel de alerta: Green, Orange o Red');

            $table->boolean('is_current')->default(true)
                ->comment('Si GDACS sigue marcando el suceso como activo');

            $table->timestamp('from_date')
                ->comment('Inicio del suceso, según GDACS');

            $table->timestamp('to_date')->nullable()
                ->comment('Fin o última fecha conocida; se desplaza mientras el suceso siga activo');

            $table->timestamp('last_modified_at')
                ->comment('Campo "datemodified" de GDACS: para saber si hay algo nuevo sin comparar el JSON entero');

            $table->float('lat')
                ->comment('Latitud del centro del suceso');

            $table->float('lon')
                ->comment('Longitud del centro del suceso');

            $table->float('distance_km')
                ->comment('Distancia en km al punto de referencia configurado, calculada al guardar');

            $table->float('severity_value')->nullable()
                ->comment('Valor numérico de severidad (hectáreas, magnitud...), según el tipo de suceso');

            $table->string('severity_unit', 16)->nullable()
                ->comment('Unidad del valor de severidad, ej. "ha" o "M"');

            $table->string('severity_text')->nullable()
                ->comment('Texto descriptivo de severidad tal cual lo da GDACS');

            $table->unsignedInteger('affected_population')->nullable()
                ->comment('Población estimada afectada. Requiere una llamada aparte por evento; no siempre se rellena');

            $table->string('report_url')->nullable()
                ->comment('Enlace al informe de GDACS para este suceso');

            $table->timestamps();

            $table->unique(['event_type', 'event_id']);
            $table->index('is_current');
            $table->index('alert_level');
            $table->index('from_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdacs_events');
    }
};
