<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Observación convencional (datos reales, no predicción) de las tres
 * estaciones AEMET cercanas a Chipiona — `observacion/convencional/datos/estacion/{idema}`.
 *
 * `station_zone`/`station_id` son los dos únicos campos identificativos de la
 * estación: nada de lat/lon/altitud, que no cambian y no aportan sobre el
 * nombre/id (ver docs/future/archived/revisar-aemet.md). Aquí sí hace falta
 * identificar la estación fila a fila, a diferencia de
 * `meteorology_aemet_uvi`/`_daily_predictions`, porque aquí sí hay más de una.
 *
 * Verificado en directo el 2026-09-16 contra las tres estaciones: Rota
 * (`5910X`) no reporta viento en ninguno de sus registros, así que las
 * columnas de viento son `nullable` de verdad, no "opcional en la práctica".
 * `dew_point`/`pressure`/`visibility`/`snow_depth` tampoco llegan hoy en
 * ninguna de las tres, pero se guardan igual a la espera (decisión del
 * usuario 2026-09-16): si en unas semanas/meses siguen sin llegar y no
 * aparece otra estación cercana que sí los dé, se valora quitarlas.
 */
return new class extends Migration
{
    private string $tableName = 'meteorology_aemet_station_observations';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Observación convencional (dato real, no predicción) de las estaciones AEMET cercanas a Chipiona');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->string('station_zone', 32)
                ->comment('Nombre que le damos nosotros a la estación: chipiona_eca, rota_base_naval, almonte. No viene de la API');

            $table->string('station_id', 16)
                ->comment('idema de AEMET (5906X, 5910X, 5858X)');

            $table->timestamp('observed_at')
                ->comment('Fin del periodo de observación (fint). El dato es de la hora ANTERIOR a la indicada');

            $table->float('temperature')->nullable()
                ->comment('Temperatura instantánea del aire en ºC (ta)');

            $table->float('temperature_min')->nullable()
                ->comment('Mínima de los 60 valores instantáneos de la hora, ºC (tamin)');

            $table->float('temperature_max')->nullable()
                ->comment('Máxima de los 60 valores instantáneos de la hora, ºC (tamax)');

            $table->float('dew_point')->nullable()
                ->comment('Temperatura del punto de rocío en ºC (tpr). Ninguna de las tres estaciones lo reporta hoy; se deja a la espera');

            $table->float('humidity')->nullable()
                ->comment('Humedad relativa instantánea en % (hr)');

            $table->float('precipitation_mm')->nullable()
                ->comment('Precipitación acumulada 60 min por pluviómetro, mm = l/m² (prec)');

            $table->float('pressure')->nullable()
                ->comment('Presión en el nivel del barómetro, hPa (pres). Ninguna de las tres estaciones lo reporta hoy; se deja a la espera');

            $table->float('visibility')->nullable()
                ->comment('Visibilidad, promedio de 10 min (vis). Ninguna de las tres estaciones lo reporta hoy; se deja a la espera');

            $table->float('snow_depth')->nullable()
                ->comment('Espesor de la capa de nieve en cm, 10 min (nieve). Ninguna de las tres estaciones lo reporta hoy; se deja a la espera');

            $table->float('wind_speed')->nullable()
                ->comment('Velocidad media del viento, media escalar, m/s (vv). Rota no lo reporta');

            $table->float('wind_gust')->nullable()
                ->comment('Racha máxima: máximo mantenido 3 s en los 60 min, m/s (vmax). Rota no lo reporta');

            $table->float('wind_direction')->nullable()
                ->comment('Dirección media del viento, 10 min anteriores, grados (dv). Rota no lo reporta');

            $table->float('wind_gust_direction')->nullable()
                ->comment('Dirección del viento máximo de los 60 min, grados (dmax). Rota no lo reporta');

            $table->timestamps();

            $table->unique(['station_id', 'observed_at'], 'meteorology_aemet_station_observations_station_time_unique');
            $table->index('station_zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
