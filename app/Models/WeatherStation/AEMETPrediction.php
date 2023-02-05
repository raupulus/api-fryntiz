<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETPrediction extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_predictions';

    protected $fillable = [
        'sky_status',
        'sky_status_code',
        'start_at',
        'end_at',
        'rain',
        'rain_prob',
        'storm_prob',
        'snow',
        'snow_prob',
        'temperature',
        'thermal_sensation',
        'humidity',
        'sunrise',
        'sunset',
        'city',
        'province',
        'day_start_at',
        'day_end_at',
    ];


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
            'city' => 'nullable|string',
            'province' => 'nullable|string',
            'sky_status' => 'nullable|string',
            'sky_status_code' => 'nullable|string',
            'rain' => 'nullable|numeric',
            'rain_prob' => 'nullable|integer',
            'storm_prob' => 'nullable|integer',
            'snow' => 'nullable|numeric',
            'snow_prob' => 'nullable|integer',
            'temperature' => 'nullable|numeric',
            'thermal_sensation' => 'nullable|numeric',
            'humidity' => 'nullable|integer',
            'sunrise' => 'nullable',
            'sunset' => 'nullable',
            'start_at' => 'required',
            'end_at' => 'required',
            'day_start_at' => 'required',
            'day_end_at' => 'required',
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
        return !( (bool)self::validation($datas)->fails() );
    }

    /**
     * Recibe la respuesta de la api y procesa todos los elementos a guardar.
     *
     * @param array $apiResponse Una matriz con los elementos a guardar
     *
     * @return array|null
     */
    public static function saveFromApi(array $apiResponse): ?array
    {

        $response = [];

        foreach ($apiResponse as $element) {
            if (self::isValid($element)) {
                $response[] = self::updateOrCreate(
                    [
                        'start_at' => $element['start_at'],
                    ],
                    self::validation($element)->validated(),
                );
            }
        }

        return count($response) ? $response : null;
    }
}
