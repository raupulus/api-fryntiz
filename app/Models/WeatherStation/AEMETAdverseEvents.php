<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETAdverseEvents extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'polygon', 'others_fields_json', 'read_at'];

    protected $table = 'meteorology_aemet_adverse_events';


    /**
     * Comprueba si los datos coinciden con lo que puede guardarse en el modelo.
     *
     * @param array $datas Un array de datos que debe coincidir con $fillable
     *
     * @return bool
     */
    public static function validation(array $datas): bool
    {
        $validator = Validator::make($datas, [
            'name' => 'required|max:255',
            'slug' => 'required|max:255',
            'polygon' => 'nullable|string',
            'others_fields_json' => 'nullable|string',
            'read_at' => 'required',
        ]);

        return ! ((bool) $validator->fails());
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

            if (self::validation($register)) {
                $result[] = self::updateOrCreate(
                    [
                        'slug' => $register['slug'],
                        'read_at' => $register['read_at']
                    ],
                    $register
                );
            }
        }

        return count($result) ? $result : null;
    }
}
