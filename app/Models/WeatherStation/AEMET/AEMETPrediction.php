<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string|null $city Ciudad sobre la que se piden las predicciones
 * @property string|null $province Provincia en la que se encuentra la ciudad
 * @property string|null $sky_status Descripción del estado del cielo
 * @property string|null $sky_status_code Código del estado del Cielo
 * @property numeric|null $rain Cantidad total de precipitación durante la hora anterior (mm)
 * @property int|null $rain_prob Valor de la probabilidad de precipitación (%)
 * @property int|null $storm_prob Valor de la probabilidad de tormenta (%)
 * @property numeric|null $snow Cantidad total de nieve que se prevé que caiga durante la hora anterior (mm)
 * @property int|null $snow_prob Valor de la probabilidad de precipitación de nieve (%)
 * @property numeric|null $temperature Valor de la temperatura (ºC)
 * @property numeric|null $thermal_sensation Sensación térmica (ºC)
 * @property int|null $humidity Valor de la humedad relativa (%)
 * @property string $sunrise
 * @property string $sunset
 * @property string $start_at
 * @property string $end_at
 * @property string $day_start_at
 * @property string $day_end_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereDayEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereDayStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereProvince($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereRain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereRainProb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereSkyStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereSkyStatusCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereSnow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereSnowProb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereStormProb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereSunrise($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereSunset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereThermalSensation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETPrediction whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
     * @param  array  $datas  Un array de datos que debe coincidir con $fillable
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
