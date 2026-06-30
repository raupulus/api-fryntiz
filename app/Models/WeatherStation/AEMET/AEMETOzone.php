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
 * @property int $height Altura en metros alcanzada por el globo en metros geopotenciales
 * @property int $humidity Humedad relativa en %
 * @property numeric $pressure Presión atmosférica en hPa
 * @property numeric $temperature Temperatura en el aire en grados centígrados ºC
 * @property numeric $temperature_virtual La temperatura que tendría el aire seco" en ºC
 * @property numeric $diff_temperature_dew_point Diferencia entre la temperatura y el punto de rocío en ºC
 * @property numeric $diff_temperature_per_height_km Temperatura entre 2 puntos a 1 km de diferencia en altura ascendente, unidad de medida ºC/km (grados centígrados por kilómetro subido)
 * @property numeric $rate_of_elevation Velocidad de ascenso en m/s de la ozonosonda
 * @property numeric $ozone_partial_pressure Presión parcial de ozono en mPa, presión de ozono si se eliminaran todos los componentes de la mezcla y sin variación de temperatura
 * @property numeric|null $device_internal_temperature Temperatura interna del dispositivo en ºC
 * @property numeric $ozone_integrated Concentración de ozono en Dobson (Dobson es una unidad de medida de concentración de ozono en la atmósfera terrestre)
 * @property numeric $ozone_residual Ozono residual de la columna. Residuo de ozono en Dobson (Dobson es una unidad de medida de concentración de ozono en la atmósfera terrestre)
 * @property int $time_min Minutos desde el lanzamiento del sondeo
 * @property int $time_s Segundos desde el lanzamiento del sondeo
 * @property string $ozone_probe_read_at Fecha y hora de la lectura de la ozonosonda
 * @property string $ozone_probe_launch_at Fecha y hora del lanzamiento de la ozonosonda
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereDeviceInternalTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereDiffTemperatureDewPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereDiffTemperaturePerHeightKm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereOzoneIntegrated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereOzonePartialPressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereOzoneProbeLaunchAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereOzoneProbeReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereOzoneResidual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereRateOfElevation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereTemperatureVirtual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereTimeMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereTimeS($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzone whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AEMETOzone extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_ozone';

    protected $fillable = [
        'pressure', // Presión atmosférica en hPa
        'height', // Altura en metros alcanzada por el globo en metros geopotenciales.
        'temperature', // Temperatura en el aire en grados centígrados ºC
        'humidity', // Humedad relativa en %
        'temperature_virtual', // Temperatura virtual en ºC
        'diff_temperature_dew_point', // Diferencia entre la temperatura y el punto de rocío en ºC
        'diff_temperature_per_height_km', // Temperatura entre 2 puntos a 1 km de diferencia en altura ascendente, unidad de medida ºC/km (grados centígrados por kilómetro subido)
        'rate_of_elevation', // Velocidad de ascenso en m/s de la ozonosonda
        'ozone_partial_pressure', // Presión parcial de ozono en mPa, presión de ozono si se eliminaran todos los componentes de la mezcla y sin variación de temperatura
        'device_internal_temperature', // Temperatura interna del dispositivo en ºC
        'time_min', // Minutos desde el lanzamiento del sondeo
        'time_s', // Segundos desde el lanzamiento del sondeo
        'ozone_integrated',
        'ozone_residual',
        'ozone_probe_read_at',
        'ozone_probe_launch_at',
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
            'pressure' => 'required|numeric',
            'height' => 'required|numeric',
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric',
            'temperature_virtual' => 'required|numeric',
            'diff_temperature_dew_point' => 'required|numeric',
            'diff_temperature_per_height_km' => 'required|numeric',
            'rate_of_elevation' => 'required|numeric',
            'ozone_partial_pressure' => 'required|numeric',
            'device_internal_temperature' => 'nullable|numeric',
            'ozone_integrated' => 'required|numeric',
            'ozone_residual' => 'required|numeric',
            'ozone_probe_read_at' => 'required',
            'ozone_probe_launch_at' => 'required',
            'time_min' => 'required|numeric',
            'time_s' => 'required|numeric',
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
                        'ozone_probe_read_at' => $apiResponse['ozone_probe_read_at'],
                    ],
                    self::validation($apiResponse)->validated(),
                );
            }
        }

        return $result ?? null;
    }
}
