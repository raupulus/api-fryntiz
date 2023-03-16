<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETContamination extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_contamination';

    protected $fillable = ['date', 'time', 'so2', 'no',
        'no2', 'o3', 'wind_speed', 'wind_direction',
        'temperature', 'humidity', 'pressure', 'radiation_global',
        'rain', 'read_at'
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
            'so2' => 'nullable|numeric',
            'no' => 'nullable|numeric',
            'no2' => 'nullable|numeric',
            'o3' => 'nullable|numeric',
            'wind_speed' => 'nullable|numeric',
            'wind_direction' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'pressure' => 'nullable|numeric',
            'radiation_global' => 'nullable|numeric',
            'rain' => 'nullable|numeric',
            'date' => 'required',
            'time' => 'required',
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
        return !( (bool)self::validation($datas)->fails() );
    }

    /**
     * Recibe la respuesta de la api y procesa todos los elementos a guardar.
     *
     * @param array $apiResponse Una matriz con los elementos a guardar
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public static function saveFromApi(array $apiResponseArray): ?array
    {
        $result = [];

        foreach ($apiResponseArray as $apiResponse) {

            if (self::isValid($apiResponse)) {
                $result[] = self::updateOrCreate(
                    [
                        'read_at' => $apiResponse['read_at'],
                    ],
                    self::validation($apiResponse)->validated(),
                );
            }
        }

        return $result ?? null;
    }
}
