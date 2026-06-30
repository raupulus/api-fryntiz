<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string|null $start_at Momento de inicio para el periodo de validez de la lectura
 * @property string $end_at Momento de fin para el periodo de validez de la lectura
 * @property string $general_id Código de la zona de playa
 * @property string|null $general_name Nombre de la zona de playa
 * @property string|null $general_slug Slug de la zona de playa
 * @property string|null $general_text Texto de la zona de playa
 * @property string|null $zone_id Código de la zona de playa
 * @property string|null $zone_name Nombre de la zona de playa
 * @property string|null $zone_slug Slug de la zona de playa
 * @property string $subzone_id Código de la subzona de playa
 * @property string|null $subzone_name Nombre de la subzona de playa
 * @property string|null $subzone_slug Slug de la subzona de playa
 * @property string|null $subzone_text Texto de la subzona de playa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereGeneralId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereGeneralName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereGeneralSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereGeneralText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereSubzoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereSubzoneName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereSubzoneSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereSubzoneText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereZoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereZoneName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETCoast whereZoneSlug($value)
 *
 * @mixin \Eloquent
 */
class AEMETCoast extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_prediction_coasts';

    protected $fillable = ['start_at', 'end_at', 'general_id', 'general_name',
        'general_slug', 'general_text', 'zone_id', 'zone_name',
        'zone_slug', 'subzone_id', 'subzone_name', 'subzone_slug',
        'subzone_text',
    ];

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
