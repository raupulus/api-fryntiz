<?php

namespace App\Api\WeatherStation\Controllers;

use App\AirQuality;
use App\Eco2;
use App\Humidity;
use App\Light;
use App\Lightning;
use App\Pressure;
use App\Temperature;
use App\Tvoc;
use App\Uva;
use App\Uvb;
use App\UvIndex;
use App\WindDirection;
use Illuminate\Support\Facades\DB;
use function dd;
use function response;

class GeneralController
{
    /**
     * Devuelve a modo de resumen los datos básicos para el tiempo en la menor
     * cantidad de consultas posibles.
     */
    public function resume()
    {
        /*
        $temp = Temperature::select('meteorology_temperature.value as temperature')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();
        */

        $eco2 = Eco2::select('meteorology_eco2.value as eco2')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $airQuality = AirQuality::select('meteorology_air_quality.air_quality as air_quality')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $humidity = Humidity::select('meteorology_humidity.value as humidity')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $light = Light::select('meteorology_light.value as light')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $lightning = Lightning::select('meteorology_lightning.created_at as last_lightning_at')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $pressure = Pressure::select('meteorology_pressure.value as pressure')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $tvoc = Tvoc::select('meteorology_tvoc.value as tvoc')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $uva = Uva::select('meteorology_uva.value as uva')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $uvb = Uvb::select('meteorology_uvb.value as uvb')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $uv_index = UvIndex::select('meteorology_uv_index.value as uv_index')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $wind_direction = WindDirection::select('meteorology_wind_direction.direction as wind_direction')
            ->orderByDesc('created_at')
            ->limit(1)
            ->toSql();

        $data = Temperature::select([
                'meteorology_temperature.value as temperature',
                DB::raw("($airQuality) as air_quality"),
                DB::raw("($eco2) as eco2"),
                DB::raw("($humidity) as humidity"),
                DB::raw("($light) as light"),
                DB::raw("($lightning) as last_lightning_at"),
                DB::raw("($pressure) as pressure"),
                DB::raw("($tvoc) as tvoc"),
                DB::raw("($uv_index) as uv_index"),
                DB::raw("($uva) as uva"),
                DB::raw("($uvb) as uvb"),
                DB::raw("($wind_direction) as wind_direction"),
            ])
            ->orderByDesc('created_at')
            ->first()
            ->toArray();

        /*
        $data =  DB::table(DB::raw('meteorology_temperature temperature, meteorology_air_quality air_quality'))
            ->select([
                'temperature.value as temperature',
                'air_quality.air_quality as air_quality',
            ])
            ->joinSub($temp, 'value')
            ->orderBy('temperature.created_at', 'desc')
            ->take(10)
            ->get();

        $data = DB::table('meteorology_air_quality')
            ->select([
                'meteorology_air_quality.air_quality as air_quality',
                'meteorology_temperature.value as temperature'
            ])
            ->unionAll($temp)
            ->orderBy('meteorology_air_quality.created_at')
            ->orderBy('meteorology_temperature.created_at')
            ->first();
        */

        dd($data);

        return response()->json($data);
    }
}
