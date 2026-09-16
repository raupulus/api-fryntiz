<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ozono total en superficie, una fila por estación y día
 * (`red/especial/ozono`; no confundir con `meteorology_aemet_ozone`, que es el
 * perfil vertical de una ozonosonda — ver docs/future/archived/revisar-aemet.md).
 */
return new class extends Migration
{
    private string $tableName = 'meteorology_aemet_ozone_total';

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Ozono total diario en superficie por estación (red/especial/ozono)');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->string('station_name', 255)
                ->comment('Nombre de la estación, tal cual lo manda AEMET');

            $table->string('station_code', 32)
                ->comment('Indicativo climatológico de la estación');

            $table->integer('ozone_value')
                ->comment('Dato medio diario del contenido total de ozono, en Unidades Dobson');

            $table->date('measured_on')
                ->comment('Fecha del dato, tal cual la publica AEMET (formato dd-mm-aa)');

            $table->timestamps();

            $table->unique(['station_code', 'measured_on'], 'meteorology_aemet_ozone_total_station_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
