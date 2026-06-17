<?php

namespace App\Models\WeatherStation\AEMET;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string $station_code Código de la estación, indicativo Climatológico Estación
 * @property string $type_global Variable medida global
 * @property string $type_diffuse Variable medida difusa
 * @property string $type_direct Variable medida directa
 * @property string $type_uv_eritematica Variable medida UV Eritemática
 * @property string $type_infrarroja Variable medida Infrarroja
 * @property string $real_solar_hour_global Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Global. Unidad de medida 10*kJ/m2
 * @property string $real_solar_hour_diffuse Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Difusa. Unidad de medida 10*kJ/m2
 * @property string $real_solar_hour_direct Radiación horaria acumulada entre: (hora indicada -1) y (hora indicada) entre las 5 y las 20 Hora Solar Verdadera. Directa. Unidad de medida 10*kJ/m2
 * @property string|null $sum_global Radiación diaria acumulada. Suma Global. Unidad de medida 10*kJ/m2
 * @property string|null $sum_diffuse Radiación diaria acumulada. Suma Difusa. Unidad de medida 10*kJ/m2
 * @property string|null $sum_direct Radiación diaria acumulada. Suma Directa. Unidad de medida 10*kJ/m2
 * @property string $real_solar_hour_uver Radiación semihoraria acumulada entre: (hora:minutos indicados - 30 minutos y (hora:minutos indicados) entre las 4:30 y las 20 Hora  Solar Verdadera. Variables: Radiación Ultravioleta Eritemática. Unidad de medida J/m2
 * @property string|null $sum_uver Radiación diaria acumulada. Variables: Radiación Ultravioleta Eritemática.  Unidad de medida J/m2
 * @property string|null $real_solar_hour_infrared Radiación horaria acumulada entre (hora indicada -1) y (hora indicada) entre las 1 y las 24 Hora Solar Verdadera. Variables: Radiación Infrarroja. Unidad de medida 10*kJ/m2
 * @property string|null $sum_infrared Radiación diaria acumulada. Variables: Radiación Infrarroja. Unidad de medida 10*kJ/m2
 * @property string $start_at
 * @property string $end_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereRealSolarHourDiffuse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereRealSolarHourDirect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereRealSolarHourGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereRealSolarHourInfrared($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereRealSolarHourUver($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereStationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereSumDiffuse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereSumDirect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereSumGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereSumInfrared($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereSumUver($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereTypeDiffuse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereTypeDirect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereTypeGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereTypeInfrarroja($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereTypeUvEritematica($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETSunRadiation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AEMETSunRadiation extends Model
{
    use HasFactory;

    protected $table = 'meteorology_aemet_sun_radiation';

    protected $fillable = [
        'station_code',
        'type_global',
        'type_diffuse',
        'type_direct',
        'type_uv_eritematica',
        'type_infrarroja',
        'real_solar_hour_global',
        'real_solar_hour_diffuse',
        'real_solar_hour_direct',
        'sum_global',
        'sum_diffuse',
        'sum_direct',
        'real_solar_hour_uver',
        'sum_uver',
        'real_solar_hour_infrared',
        'sum_infrared',
        'start_at',
        'end_at',
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
            'station_code' => 'required|string',
            'type_global' => 'required|string',
            'type_diffuse' => 'required|string',
            'type_direct' => 'required|string',
            'type_uv_eritematica' => 'required|string',
            'type_infrarroja' => 'required|string',
            'real_solar_hour_global' => 'required|string',
            'real_solar_hour_diffuse' => 'required|string',
            'real_solar_hour_direct' => 'required|string',
            'sum_global' => 'nullable|string',
            'sum_diffuse' => 'nullable|string',
            'sum_direct' => 'nullable|string',
            'real_solar_hour_uver' => 'required|string',
            'sum_uver' => 'nullable|string',
            'real_solar_hour_infrared' => 'required|string',
            'sum_infrared' => 'nullable|string',
            'start_at' => 'required',
            'end_at' => 'required',
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
    public static function saveFromApi(array $apiResponse): ?self
    {
        if (self::isValid($apiResponse)) {
            return self::updateOrCreate(
                [
                    'end_at' => $apiResponse['end_at'],
                ],
                self::validation($apiResponse)->validated(),
            );
        }

        return null;
    }
}
