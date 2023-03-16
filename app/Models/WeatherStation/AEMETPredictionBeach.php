<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETPredictionBeach extends Model
{
    use HasFactory;

    protected $fillable = ['beach_id', 'name', 'slug', 'city_code', 'read_at',
        'sky_morning_status_code', 'sky_morning_status', 'sky_afternoon_status_code',
        'sky_afternoon_status', 'sky_extra_info', 'wind_morning_status_code', 'wind_morning_status', 'wind_afternoon_status_code', 'wind_afternoon_status',
        'wind_extra_info', 'wave_morning_status_code', 'wave_morning_status',
        'wave_afternoon_status_code', 'wave_afternoon_status', 'wave_extra_info', 'wave_extra_info', 'temperature_max', 'temperature_max_extra_info',
        'thermal_sensation_status_code', 'thermal_sensation_status', 'thermal_sensation_extra_info', 'water_temperature', 'water_temperature_extra_info', 'uv_max', 'uv_max_extra_info', 'date'
        ];

    protected $table = 'meteorology_aemet_prediction_beachs';

    /**
     * Ejecuta la validación sobre los datos recibidos y devuelve un obejto
     * "Validator".
     *
     * @param array $datas Un array de datos que debe coincidir con $fillable
     *
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
     * @param array $datas Un array de datos que debe coincidir con $fillable
     *
     * @return bool
     */
    public static function isValid(array $datas): bool
    {
        return ! ((bool) self::validation($datas)->fails());
    }

    /**
     * Recibe la respuesta de la api y procesa todos los elementos a guardar.
     *
     * @param array $apiResponse Una matriz con los elementos a guardar
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public static function saveFromApi(array $apiResponse): ?array
    {
        $result = [];

        foreach ($apiResponse as $register) {
            if (self::isValid($register)) {
                $result[] = self::updateOrCreate(
                    [
                        'beach_id' => $register['beach_id'],
                        'date' => $register['date']
                    ],
                    self::validation($register)->validated(),
                );
            }
        }

        return count($result) ? $result : null;
    }
}
