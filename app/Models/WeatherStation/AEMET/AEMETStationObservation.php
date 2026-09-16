<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string $station_zone Nombre que le damos nosotros a la estación (chipiona_eca, rota_base_naval, almonte)
 * @property string $station_id idema de AEMET
 * @property Carbon $observed_at Fin del periodo de observación (fint)
 * @property float|null $temperature Temperatura instantánea del aire (ºC)
 * @property float|null $temperature_min Mínima de la hora (ºC)
 * @property float|null $temperature_max Máxima de la hora (ºC)
 * @property float|null $dew_point Temperatura del punto de rocío (ºC)
 * @property float|null $humidity Humedad relativa instantánea (%)
 * @property float|null $precipitation_mm Precipitación acumulada 60 min (mm)
 * @property float|null $pressure Presión en el barómetro (hPa)
 * @property float|null $visibility Visibilidad, promedio de 10 min
 * @property float|null $snow_depth Espesor de la capa de nieve (cm)
 * @property float|null $wind_speed Velocidad media del viento (m/s)
 * @property float|null $wind_gust Racha máxima (m/s)
 * @property float|null $wind_direction Dirección media del viento (grados)
 * @property float|null $wind_gust_direction Dirección del viento máximo (grados)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> newModelQuery()
 * @method static Builder<static> newQuery()
 * @method static Builder<static> query()
 * @method static Builder<static> forZone(string $zone)
 *
 * @mixin \Eloquent
 */
class AEMETStationObservation extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_station_observations';

    protected $fillable = [
        'station_zone',
        'station_id',
        'observed_at',
        'temperature',
        'temperature_min',
        'temperature_max',
        'dew_point',
        'humidity',
        'precipitation_mm',
        'pressure',
        'visibility',
        'snow_depth',
        'wind_speed',
        'wind_gust',
        'wind_direction',
        'wind_gust_direction',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'temperature' => 'float',
        'temperature_min' => 'float',
        'temperature_max' => 'float',
        'dew_point' => 'float',
        'humidity' => 'float',
        'precipitation_mm' => 'float',
        'pressure' => 'float',
        'visibility' => 'float',
        'snow_depth' => 'float',
        'wind_speed' => 'float',
        'wind_gust' => 'float',
        'wind_direction' => 'float',
        'wind_gust_direction' => 'float',
    ];

    public function scopeForZone(Builder $query, string $zone): Builder
    {
        return $query->where('station_zone', $zone);
    }

    /**
     * Solo `station_id`/`observed_at` son obligatorios: los 39 campos de
     * observación son 34 opcionales, y las tres estaciones no traen los
     * mismos (Rota no reporta viento) — ver
     * docs/future/archived/revisar-aemet.md.
     */
    public static function validation(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'station_zone' => 'required|string|max:32',
            'station_id' => 'required|string|max:16',
            'observed_at' => 'required|date',
            'temperature' => 'nullable|numeric',
            'temperature_min' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'dew_point' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'precipitation_mm' => 'nullable|numeric',
            'pressure' => 'nullable|numeric',
            'visibility' => 'nullable|numeric',
            'snow_depth' => 'nullable|numeric',
            'wind_speed' => 'nullable|numeric',
            'wind_gust' => 'nullable|numeric',
            'wind_direction' => 'nullable|numeric',
            'wind_gust_direction' => 'nullable|numeric',
        ]);
    }

    public static function isValid(array $data): bool
    {
        return ! self::validation($data)->fails();
    }

    /**
     * Recibe las filas ya parseadas de una estación (hasta ~13 por sondeo,
     * últimas 12h) y las guarda. Un sondeo repetido sobre la misma hora
     * actualiza, no duplica.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, self>
     */
    public static function saveFromApi(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            if (! self::isValid($row)) {
                continue;
            }

            $result[] = self::updateOrCreate(
                [
                    'station_id' => $row['station_id'],
                    'observed_at' => $row['observed_at'],
                ],
                self::validation($row)->validated(),
            );
        }

        return $result;
    }
}
