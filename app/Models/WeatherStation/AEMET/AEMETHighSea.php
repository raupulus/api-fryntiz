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
 * @property int $zone_code Código de la zona de altamar
 * @property string|null $start_at Momento de inicio para el periodo de validez de la lectura
 * @property string $end_at Momento de fin para el periodo de validez de la lectura
 * @property string|null $text Contiene la información de la predicción
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETHighSea whereZoneCode($value)
 *
 * @mixin \Eloquent
 */
class AEMETHighSea extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_high_seas';

    protected $fillable = ['zone_code', 'text', 'end_at', 'start_at'];

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
            'zone_code' => 'required|integer',
            'text' => 'required',
            'end_at' => 'required',
            'start_at' => 'nullable',
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
