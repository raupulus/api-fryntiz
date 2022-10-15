<?php

namespace App\Http\Controllers\Api\WeatherStation;

use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Uva;
use App\Models\WeatherStation\Uvb;
use App\Models\WeatherStation\UvIndex;
use App\Models\WeatherStation\WindDirection;
use App\Models\WeatherStation\Winter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use function count;
use function response;

/**
 * Class GeneralController
 *
 * @package App\Http\Controllers\Api\WeatherStation
 */
class GeneralController
{
    /**
     * Devuelve a modo de resumen los datos básicos para el tiempo en la menor
     * cantidad de consultas posibles.
     */
    public function resume()
    {

        $data = Cache::remember('ws.resume', 60, function () {
            $now = Carbon::now();
            $lastTenMinutes = (clone($now))->subMinutes(10);

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

            $wind_average = Winter::select('meteorology_winter.average as wind_average')
                ->orderByDesc('created_at')
                ->limit(1)
                ->toSql();

            $wind_min = Winter::select('meteorology_winter.min as wind_min')
                ->orderByDesc('created_at')
                ->limit(1)
                ->toSql();

            $wind_max = Winter::select('meteorology_winter.max as wind_max')
                ->orderByDesc('created_at')
                ->limit(1)
                ->toSql();

            $d = Temperature::select([
                'meteorology_temperature.value as temperature',
                DB::raw("($airQuality) as air_quality"),
                DB::raw("($eco2) as eco2"),
                DB::raw("($humidity) as humidity"),
                DB::raw("($light) as light"),
                DB::raw("($lightning) as last_lightning_at"),
                //DB::raw("($lightningQuantityLastTenMinutes) as
                // lightningQuantityLastTenMinutes"),
                DB::raw("($pressure) as pressure"),
                DB::raw("($tvoc) as tvoc"),
                DB::raw("($uv_index) as uv_index"),
                DB::raw("($uva) as uva"),
                DB::raw("($uvb) as uvb"),
                DB::raw("($wind_direction) as wind_direction"),
                DB::raw("($wind_average) as wind_average"),
                DB::raw("($wind_min) as wind_min"),
                DB::raw("($wind_max) as wind_max"),
            ])
                ->orderByDesc('created_at')
                ->first()
                ->toArray();

            $lightningQuantityLastTenMinutes = Lightning::selectRaw('count(*) as qm')
                ->where('created_at', '>=', $lastTenMinutes)
                ->limit(1)
                ->pluck('qm')
                ->first();

            $d['lightningQuantityLastTenMinutes'] =  $lightningQuantityLastTenMinutes;

            return $d;
        });

        $now = Carbon::now();
        $now->tz = 'Europe/Madrid';

        $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                   'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre',
                   'Diciembre'];

        $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        ## Almaceno el momento de la comprobación
        $data['instant'] = [
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'year' => $now->format('Y'),
            'month' => $now->format('m'),
            'month_name' => $months[$now->format('m') - 1],
            'day' => (int) $now->format('d'),
            'day_week' => $now->dayOfWeek,
            'day_name' => $days[$now->dayOfWeek],
            'date_human_format' => $now->format('d') . ' ' .$months[$now->format('m') - 1] . ' ' . $now->format('Y'),
            'time' => $now->format('H:i:s'),

            //En el futuro mostrar tiempo: Muy Soleado, día, noche, lluvia...
            'day_status' => (($now->format('H') >= 20) || ($now->format('H') <= 8)) ?
                            'Noche' :
                            'Día',
        ];

        return response()->json($data);
    }
}
