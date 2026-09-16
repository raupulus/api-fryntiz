<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Predicción diaria de municipio (`prediccion/especifica/municipio/diaria/11016`).
 * Complementa a `meteorology_aemet_predictions` (horaria) con el resumen
 * "hoy/mañana"; siete días por sondeo, una fila por día, `unique(date)`.
 *
 * Estructura verificada CONTRA LA API REAL el 2026-09-16 (429 al intentar
 * desde la máquina de desarrollo, resuelto ejecutando la petición desde un
 * VPS con otra IP — la cuota de AEMET va ligada a IP, no sólo a la clave).
 * No es la reconstrucción a partir de la horaria que decía antes este
 * comentario: son productos con forma distinta.
 *
 * Sin `municipality_code`/`city`/`province`: esta API es siempre Chipiona
 * (`id 11016`, en config), igual que en `meteorology_aemet_uvi` — ver
 * docs/future/archived/revisar-aemet.md.
 */
return new class extends Migration
{
    private string $tableName = 'meteorology_aemet_daily_predictions';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Predicción diaria de Chipiona, un resumen por día (prediccion/especifica/municipio/diaria)');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->date('date')
                ->comment('Día al que corresponde la predicción (dia[].fecha)');

            $table->string('sky_status')->nullable()
                ->comment('Descripción del estado del cielo del tramo "00-24" (o el único tramo si no hay más), dia[].estadoCielo[].descripcion');

            $table->string('sky_status_code', 8)->nullable()
                ->comment('Código del estado del cielo, dia[].estadoCielo[].value');

            $table->unsignedTinyInteger('rain_prob')->nullable()
                ->comment('Probabilidad de precipitación del día completo, % (dia[].probPrecipitacion)');

            $table->string('snow_level', 16)->nullable()
                ->comment('Cota de nieve provincial, tal cual la manda AEMET (dia[].cotaNieveProv). Vacía la inmensa mayoría del año en Cádiz');

            $table->string('wind_direction', 4)->nullable()
                ->comment('Dirección del viento del día completo, punto cardinal (dia[].viento[].direccion)');

            $table->float('wind_speed')->nullable()
                ->comment('Velocidad del viento del día completo, km/h (dia[].viento[].velocidad)');

            $table->float('wind_gust')->nullable()
                ->comment('Racha máxima del día, km/h (dia[].rachaMax). Llega como cadena y puede venir vacía');

            $table->float('temperature_max')->nullable()
                ->comment('Temperatura máxima del día, ºC (dia[].temperatura.maxima)');

            $table->float('temperature_min')->nullable()
                ->comment('Temperatura mínima del día, ºC (dia[].temperatura.minima)');

            $table->float('thermal_sensation_max')->nullable()
                ->comment('Sensación térmica máxima, ºC (dia[].sensTermica.maxima)');

            $table->float('thermal_sensation_min')->nullable()
                ->comment('Sensación térmica mínima, ºC (dia[].sensTermica.minima)');

            $table->float('humidity_max')->nullable()
                ->comment('Humedad relativa máxima, % (dia[].humedadRelativa.maxima)');

            $table->float('humidity_min')->nullable()
                ->comment('Humedad relativa mínima, % (dia[].humedadRelativa.minima)');

            $table->unsignedTinyInteger('uv_max')->nullable()
                ->comment('Índice UV máximo previsto para el día (dia[].uvMax). AEMET no lo manda para todos los días del rango (verificado: ausente en los dos últimos de siete)');

            $table->timestamp('elaborated_at')
                ->comment('Momento de elaboración del boletín (elaborado), igual para los siete días de un mismo sondeo');

            $table->timestamps();

            $table->unique('date', 'meteorology_aemet_daily_predictions_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
