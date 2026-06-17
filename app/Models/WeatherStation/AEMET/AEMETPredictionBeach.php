<?php

namespace App\Models\WeatherStation\AEMET;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property int $beach_id Almacena el identificador que tiene la playa
 * @property string|null $name Nombre de la zona sobre la que guardamos los datos
 * @property string|null $slug Slug creado a partir del nombre recibido en la api, con este se identificará la zona para las consultas
 * @property int|null $city_code Código de la ciudad asociada
 * @property int $sky_morning_status_code Código de estado para el cielo por la mañana
 * @property string $sky_morning_status Descripción del estado para el cielo por la mañana
 * @property int $sky_afternoon_status_code Código de estado para el cielo por la tarde
 * @property string $sky_afternoon_status Descripción del estado para el cielo por la tarde
 * @property string|null $sky_extra_info Datos extra sobre la información del cielo
 * @property int $wind_morning_status_code Código de estado para el viento por la mañana
 * @property string $wind_morning_status Descripción del estado para el viento por la mañana
 * @property int $wind_afternoon_status_code Código de estado para el viento por la tarde
 * @property string $wind_afternoon_status Descripción del estado para el viento por la tarde
 * @property string|null $wind_extra_info Datos extra sobre la información del viento
 * @property int $wave_morning_status_code Código de estado para las olas por la mañana
 * @property string $wave_morning_status Descripción del estado para las olas por la mañana
 * @property int $wave_afternoon_status_code Código de estado para las olas por la tarde
 * @property string $wave_afternoon_status Descripción del estado para las olas por la tarde
 * @property string|null $wave_extra_info Datos extra sobre la información las olas
 * @property int $temperature_max Temperatura máxima para el día
 * @property string|null $temperature_max_extra_info Información extra para la temperatura máxima
 * @property int $thermal_sensation_status_code Código de estado para la temperatura máxima
 * @property string $thermal_sensation_status Descripción del estado para temperatura máxima
 * @property string|null $thermal_sensation_extra_info Datos extra sobre la información de la temperatura máxima
 * @property int $water_temperature Temperatura del agua
 * @property string|null $water_temperature_extra_info Información extra sobre la temperatura del agua
 * @property int $uv_max Radiación UV máxima
 * @property string|null $uv_max_extra_info Información extra sobre la radiación UV máxima
 * @property string $date
 * @property string $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereBeachId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereCityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereSkyAfternoonStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereSkyAfternoonStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereSkyExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereSkyMorningStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereSkyMorningStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereTemperatureMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereTemperatureMaxExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereThermalSensationExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereThermalSensationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereThermalSensationStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereUvMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereUvMaxExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaterTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaterTemperatureExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaveAfternoonStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaveAfternoonStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaveExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaveMorningStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWaveMorningStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWindAfternoonStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWindAfternoonStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWindExtraInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWindMorningStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPredictionBeach whereWindMorningStatusCode($value)
 * @mixin \Eloquent
 */
class AEMETPredictionBeach extends Model
{
    use HasFactory;

    protected $fillable = ['beach_id', 'name', 'slug', 'city_code', 'read_at',
        'sky_morning_status_code', 'sky_morning_status', 'sky_afternoon_status_code',
        'sky_afternoon_status', 'sky_extra_info', 'wind_morning_status_code', 'wind_morning_status', 'wind_afternoon_status_code', 'wind_afternoon_status',
        'wind_extra_info', 'wave_morning_status_code', 'wave_morning_status',
        'wave_afternoon_status_code', 'wave_afternoon_status', 'wave_extra_info', 'wave_extra_info', 'temperature_max', 'temperature_max_extra_info',
        'thermal_sensation_status_code', 'thermal_sensation_status', 'thermal_sensation_extra_info', 'water_temperature', 'water_temperature_extra_info', 'uv_max', 'uv_max_extra_info', 'date',
    ];

    protected $table = 'meteorology_aemet_prediction_beachs';

    /**
     * Ejecuta la validación sobre los datos recibidos y devuelve un obejto
     * "Validator".
     *
     * @param  array  $datas  Un array de datos que debe coincidir con $fillable
     * @return Validator
     */
    public static function validation(array $datas): \Illuminate\Validation\Validator
    {
        return Validator::make($datas, [
            'beach_id' => 'required|max:255',
            'name' => 'nullable|max:255',
            'slug' => 'nullable|max:255',
            'city_code' => 'nullable',
            'send_at' => 'nullable',
            'sky_morning_status_code' => 'nullable',
            'sky_morning_status' => 'nullable|max:255',
            'sky_afternoon_status_code' => 'nullable',
            'sky_afternoon_status' => 'nullable|max:255',
            'sky_extra_info' => 'nullable',
            'wind_morning_status_code' => 'nullable',
            'wind_morning_status' => 'nullable|max:255',
            'wind_afternoon_status_code' => 'nullable',
            'wind_afternoon_status' => 'nullable|max:255',
            'wind_extra_info' => 'nullable',
            'wave_morning_status_code' => 'nullable',
            'wave_morning_status' => 'nullable|max:255',
            'wave_afternoon_status_code' => 'nullable',
            'wave_afternoon_status' => 'nullable|max:255',
            'wave_extra_info' => 'nullable',
            'temperature_max' => 'required',
            'temperature_max_extra_info' => 'nullable',
            'thermal_sensation_status_code' => 'required',
            'thermal_sensation_status' => 'required|max:255',
            'thermal_sensation_extra_info' => 'nullable',
            'water_temperature' => 'required',
            'water_temperature_extra_info' => 'nullable',
            'uv_max' => 'required',
            'uv_max_extra_info' => 'nullable',
            'date' => 'required',
            'read_at' => 'required',
        ]);
    }

    /**
     * Comprueba si los datos recibidos contienen errores.
     *
     * @param  array  $datas  Un array de datos que debe coincidir con $fillable
     */
    public static function isValid(array $datas): bool
    {
        return ! ((bool) self::validation($datas)->fails());
    }

    /**
     * Recibe la respuesta de la api y procesa todos los elementos a guardar.
     *
     * @param  array  $apiResponse  Una matriz con los elementos a guardar
     * @return Collection|null
     */
    public static function saveFromApi(array $apiResponse): ?array
    {
        $result = [];

        foreach ($apiResponse as $register) {
            if (self::isValid($register)) {
                $result[] = self::updateOrCreate(
                    [
                        'beach_id' => $register['beach_id'],
                        'date' => $register['date'],
                    ],
                    self::validation($register)->validated(),
                );
            }
        }

        return count($result) ? $result : null;
    }
}
