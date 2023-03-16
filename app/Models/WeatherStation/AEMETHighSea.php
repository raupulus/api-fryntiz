<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETHighSea extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_high_seas';

    protected $fillable = ['zone_code', 'text', 'end_at', 'start_at'];


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
            'zone_code' => 'required|integer',
            'text' => 'required',
            'end_at' => 'required',
            'start_at' => 'nullable',
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
    public static function saveFromApi(array $apiResponse): ?self
    {
        $result = null;

        if (self::isValid($apiResponse)) {
            $result = self::updateOrCreate(
                [
                    'zone_code' => $apiResponse['zone_code'],
                    'end_at' => $apiResponse['end_at'],
                ],
                self::validation($apiResponse)->validated(),
            );
        }

        return $result ?? null;
    }
}
