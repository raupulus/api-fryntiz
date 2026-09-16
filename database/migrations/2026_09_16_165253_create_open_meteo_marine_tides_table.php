<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pleamares y bajamares de Chipiona, calculadas por interpolación parabólica
 * sobre la serie horaria `sea_level_height_msl` de Open-Meteo Marine (ver
 * `config/open_meteo_marine.php` y `App\Support\WeatherStation\TideExtremesCalculator`).
 *
 * Sin lat/lon por fila: el punto de referencia es fijo (Chipiona) y vive en
 * config, no repetido en cada registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_meteo_marine_tides', function (Blueprint $table) {
            $table->comment('Pleamares y bajamares de Chipiona calculadas a partir de Open-Meteo Marine');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->string('type', 8)
                ->comment('Pleamar o Bajamar');

            $table->timestamp('happens_at')
                ->comment('Momento exacto del extremo, ya interpolado (hora de Madrid)');

            $table->float('height_m')
                ->comment('Altura del nivel del mar en metros sobre el nivel medio (MSL), interpolada');

            $table->timestamp('forecast_generated_at')
                ->comment('Cuándo se pidió la predicción que produjo este extremo, para valorar frescura');

            $table->timestamps();

            $table->unique('happens_at');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_meteo_marine_tides');
    }
};
