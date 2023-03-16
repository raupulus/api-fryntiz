<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETCoast extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_prediction_coasts';

    protected $fillable = ['start_at', 'end_at', 'general_id', 'general_name',
        'general_slug', 'general_text', 'zone_id', 'zone_name',
        'zone_slug', 'subzone_id', 'subzone_name', 'subzone_slug',
        'subzone_text'
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
            'start_at' => 'nullable',
            'end_at' => 'required',
            'general_id' => 'required|string',
            'general_name' => 'required|string',
            'general_slug' => 'required|string',
            'general_text' => 'required|string',
            'zone_id' => 'required|integer',
            'zone_name' => 'required|string',
            'zone_slug' => 'required|string',
            'subzone_id' => 'required|integer',
            'subzone_name' => 'required|string',
            'subzone_slug' => 'required|string',
            'subzone_text' => 'required|string',
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
                        'zone_id' => $apiResponse['zone_id'],
                        'subzone_id' => $apiResponse['subzone_id'],
                        'end_at' => $apiResponse['end_at'],
                    ],
                    self::validation($apiResponse)->validated(),
                );
            }
        }





        return $result ?? null;
    }
}
