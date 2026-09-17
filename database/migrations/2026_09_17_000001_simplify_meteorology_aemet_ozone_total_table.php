<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplifica la tabla de ozono total en superficie eliminando las columnas
 * redundantes de estación (`station_name`, `station_code`), ya que la plataforma
 * monitoriza exclusivamente la estación de Moguer - El Arenosillo (5860E).
 */
return new class extends Migration
{
    private string $tableName = 'meteorology_aemet_ozone_total';

    public function up(): void
    {
        // Limpiamos cualquier registro previo de otras estaciones para evitar
        // conflictos de unicidad en measured_on.
        DB::table($this->tableName)
            ->where('station_code', '!=', '5860E')
            ->delete();

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique('meteorology_aemet_ozone_total_station_date_unique');
            $table->dropColumn(['station_name', 'station_code']);
            $table->unique('measured_on', 'meteorology_aemet_ozone_total_measured_on_unique');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique('meteorology_aemet_ozone_total_measured_on_unique');

            $table->string('station_name', 255)->default('Moguer (El Arenosillo)')
                ->comment('Nombre de la estación, tal cual lo manda AEMET');

            $table->string('station_code', 32)->default('5860E')
                ->comment('Indicativo climatológico de la estación');

            $table->unique(['station_code', 'measured_on'], 'meteorology_aemet_ozone_total_station_date_unique');
        });
    }
};
