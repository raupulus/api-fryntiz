<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string $name Nombre de la zona sobre la que guardamos los datos
 * @property string $slug Slug creado a partir del nombre recibido en la api, con este se identificará la zona para las consultas
 * @property string|null $polygon Array de Coordenadas para polígonos
 * @property string|null $others_fields_json Estos son campos que no están definidos en la api pero pueden llegar, hasta ahora no hay forma de identificar un fenómeno con valores númericos para interpretarlos
 * @property string $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereOthersFieldsJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents wherePolygon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETAdverseEvents whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AEMETAdverseEvents extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'polygon', 'others_fields_json', 'read_at'];

    protected $table = 'meteorology_aemet_adverse_events';

    /**
     * Comprueba si los datos coinciden con lo que puede guardarse en el modelo.
     *
     * @param  array  $datas  Un array de datos que debe coincidir con $fillable
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
     * @param  array  $apiResponse  Una matriz con los elementos a guardar
     * @return Collection|null
     */
    public static function saveFromApi(array $apiResponse): ?array
    {

        $result = [];

        foreach ($apiResponse as $register) {

            if (self::validation($register)) {
                $result[] = self::updateOrCreate(
                    [
                        'slug' => $register['slug'],
                        'read_at' => $register['read_at'],
                    ],
                    $register
                );
            }
        }

        return count($result) ? $result : null;
    }
}
