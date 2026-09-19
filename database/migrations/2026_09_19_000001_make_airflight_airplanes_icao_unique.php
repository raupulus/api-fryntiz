<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class MakeAirFlightAirplanesIcaoUnique
 *
 * `AirFlightService::addAircraft()` busca el avión por `icao` con
 * `firstOrNew()`, pero nada en la base de datos impedía que dos peticiones
 * simultáneas (un reintento del receptor tras un timeout) lo dieran de alta
 * a la vez: en producción hay tres ICAO duplicados creados en el mismo
 * segundo (`348311`, `3483c9`, `3cc847`, julio de 2026).
 *
 * Se fusionan los duplicados que haya (se conserva la fila más antigua y se
 * le pasan las rutas y los datos que le falten) y el índice normal sobre
 * `icao` pasa a ser único. Los `icao` nulos no chocan entre sí en PostgreSQL.
 */
class MakeAirFlightAirplanesIcaoUnique extends Migration
{
    private $tableName = 'airflight_airplanes';

    /** Datos del aparato que se rellenan en la fila conservada si ahí faltan. */
    private $fillableFromDuplicates = [
        'registration', 'aircraft_type', 'category', 'wtc', 'aircraft_desc', 'country', 'flag',
    ];

    public function up()
    {
        $icaos = DB::table($this->tableName)
            ->whereNotNull('icao')
            ->groupBy('icao')
            ->havingRaw('count(*) > 1')
            ->pluck('icao');

        foreach ($icaos as $icao) {
            $this->mergeDuplicates((string) $icao);
        }

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropIndex('airflight_airplanes_icao_idx');
            $table->unique('icao', 'airflight_airplanes_icao_unique');
        });
    }

    public function down()
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique('airflight_airplanes_icao_unique');
            $table->index('icao', 'airflight_airplanes_icao_idx');
        });
    }

    private function mergeDuplicates(string $icao): void
    {
        $rows = DB::table($this->tableName)->where('icao', $icao)->orderBy('id')->get();

        $keep = $rows->first();
        $duplicateIds = $rows->skip(1)->pluck('id')->all();

        $changes = [
            'seen_first_at' => $rows->pluck('seen_first_at')->filter()->min(),
            'seen_last_at' => $rows->pluck('seen_last_at')->filter()->max(),
            'route_last_at' => $rows->pluck('route_last_at')->filter()->max(),
        ];

        foreach ($this->fillableFromDuplicates as $column) {
            if ($keep->{$column} === null || $keep->{$column} === '') {
                $changes[$column] = $rows->pluck($column)->first(fn ($value) => $value !== null && $value !== '');
            }
        }

        DB::table('airflight_routes')
            ->whereIn('airplane_id', $duplicateIds)
            ->update(['airplane_id' => $keep->id]);

        DB::table($this->tableName)->where('id', $keep->id)->update($changes);
        DB::table($this->tableName)->whereIn('id', $duplicateIds)->delete();
    }
}
