<?php

namespace App\Models\WeatherStation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class AEMETOzone extends Model
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
     * @param array $datas Un array de datos que debe coincidir con $fillable
     *
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
                        'ozone_probe_read_at' => $apiResponse['ozone_probe_read_at'],
                    ],
                    self::validation($apiResponse)->validated(),
                );
            }
        }

        return $result ?? null;
    }
}
