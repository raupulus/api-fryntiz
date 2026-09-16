<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice de radiación ultravioleta máximo previsto (`prediccion/especifica/uvi/0`).
 *
 * Sin `city_code`/`city_name`: la respuesta trae 59 capitales, pero esta API
 * es siempre Cádiz — el código de esa ciudad (`11012`, verificado en directo
 * el 2026-09-16) va en `config('aemet.uvi_city_code')`, no en cada fila de una
 * tabla donde el valor nunca cambia. Ver docs/future/archived/revisar-aemet.md
 * para el porqué de este criterio en los tres productos nuevos de AEMET.
 */
return new class extends Migration
{
    private string $tableName = 'meteorology_aemet_uvi';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Índice UV máximo previsto para la ciudad configurada (prediccion/especifica/uvi)');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedTinyInteger('uv_index')
                ->comment('Índice UV máximo previsto en condiciones de cielo despejado');

            $table->date('valid_date')
                ->comment('Fecha para la que es válida la predicción (FECHA_VALIDEZ)');

            $table->timestamp('elaborated_at')
                ->comment('Momento de elaboración del boletín (FECHA_ELABORACION)');

            $table->timestamp('modified_at')
                ->comment('Última modificación del boletín según AEMET (FECHA_MOD)');

            $table->timestamps();

            $table->unique('valid_date', 'meteorology_aemet_uvi_valid_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
