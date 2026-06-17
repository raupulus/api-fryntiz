<?php

namespace App\Models\WeatherStation\AEMET;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property numeric|null $so2 Valor de la lectura
 * @property numeric|null $no Valor de la lectura
 * @property numeric|null $no2 Valor de la lectura
 * @property numeric|null $o3 Valor de la lectura
 * @property numeric|null $pm10 Valor de la lectura
 * @property numeric|null $wind_speed Valor de la lectura
 * @property numeric|null $wind_direction Valor de la lectura
 * @property numeric|null $temperature Valor de la lectura
 * @property numeric|null $humidity Valor de la lectura
 * @property numeric|null $pressure Valor de la lectura
 * @property numeric|null $radiation_global Valor de la lectura
 * @property numeric|null $rain Valor de la lectura
 * @property string $date Fecha de la lectura
 * @property string $time Hora de la lectura
 * @property string $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereNo2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereO3($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination wherePm10($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereRadiationGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereRain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereSo2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereWindDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETContamination whereWindSpeed($value)
 * @mixin \Eloquent
 */
class AEMETContamination extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_contamination';

    protected $fillable = ['date', 'time', 'so2', 'no',
        'no2', 'o3', 'wind_speed', 'wind_direction',
        'temperature', 'humidity', 'pressure', 'radiation_global',
        'rain', 'read_at',
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
                        'read_at' => $apiResponse['read_at'],
                    ],
                    self::validation($apiResponse)->validated(),
                );
            }
        }

        return $result ?? null;
    }
}
