<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETSunRadiation extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_sun_radiation';

    protected $fillable = [
        'station_code',
        'type_global',
        'type_diffuse',
        'type_direct',
        'type_uv_eritematica',
        'type_infrarroja',
        'real_solar_hour_global',
        'real_solar_hour_diffuse',
        'real_solar_hour_direct',
        'sum_global',
        'sum_diffuse',
        'sum_direct',
        'real_solar_hour_uver',
        'sum_uver',
        'real_solar_hour_infrared',
        'sum_infrared',
        'start_at',
        'end_at',
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
            'station_code' => 'required|string',
            'type_global' => 'required|string',
            'type_diffuse' => 'required|string',
            'type_direct' => 'required|string',
            'type_uv_eritematica' => 'required|string',
            'type_infrarroja' => 'required|string',
            'real_solar_hour_global' => 'required|string',
            'real_solar_hour_diffuse' => 'required|string',
            'real_solar_hour_direct' => 'required|string',
            'sum_global' => 'nullable|string',
            'sum_diffuse' => 'nullable|string',
            'sum_direct' => 'nullable|string',
            'real_solar_hour_uver' => 'required|string',
            'sum_uver' => 'nullable|string',
            'real_solar_hour_infrared' => 'required|string',
            'sum_infrared' => 'nullable|string',
            'start_at' => 'required',
            'end_at' => 'required',
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
     * @return self|null
     */
    public static function saveFromApi(array $apiResponse): ?self
    {
        if (self::isValid($apiResponse)) {
            return self::updateOrCreate(
                [
                    'end_at' => $apiResponse['end_at'],
                ],
                self::validation($apiResponse)->validated(),
            );
        }

        return null;
    }
}
