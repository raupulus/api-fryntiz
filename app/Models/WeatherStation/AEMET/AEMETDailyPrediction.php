<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string $date Día al que corresponde la predicción (Y-m-d)
 * @property string|null $sky_status
 * @property string|null $sky_status_code
 * @property int|null $rain_prob Probabilidad de precipitación del día, %
 * @property string|null $snow_level Cota de nieve provincial, tal cual la manda AEMET
 * @property string|null $wind_direction
 * @property float|null $wind_speed Km/h
 * @property float|null $wind_gust Km/h
 * @property float|null $temperature_max
 * @property float|null $temperature_min
 * @property float|null $thermal_sensation_max
 * @property float|null $thermal_sensation_min
 * @property float|null $humidity_max
 * @property float|null $humidity_min
 * @property int|null $uv_max
 * @property Carbon $elaborated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 *
 * @mixin \Eloquent
 */
class AEMETDailyPrediction extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_daily_predictions';

    protected $fillable = [
        'date',
        'sky_status',
        'sky_status_code',
        'rain_prob',
        'snow_level',
        'wind_direction',
        'wind_speed',
        'wind_gust',
        'temperature_max',
        'temperature_min',
        'thermal_sensation_max',
        'thermal_sensation_min',
        'humidity_max',
        'humidity_min',
        'uv_max',
        'elaborated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'rain_prob' => 'integer',
        'wind_speed' => 'float',
        'wind_gust' => 'float',
        'temperature_max' => 'float',
        'temperature_min' => 'float',
        'thermal_sensation_max' => 'float',
        'thermal_sensation_min' => 'float',
        'humidity_max' => 'float',
        'humidity_min' => 'float',
        'uv_max' => 'integer',
        'elaborated_at' => 'datetime',
    ];

    public static function validation(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'date' => 'required|date',
            'sky_status' => 'nullable|string|max:255',
            'sky_status_code' => 'nullable|string|max:8',
            'rain_prob' => 'nullable|integer|min:0|max:100',
            'snow_level' => 'nullable|string|max:16',
            'wind_direction' => 'nullable|string|max:4',
            'wind_speed' => 'nullable|numeric',
            'wind_gust' => 'nullable|numeric',
            'temperature_max' => 'nullable|numeric',
            'temperature_min' => 'nullable|numeric',
            'thermal_sensation_max' => 'nullable|numeric',
            'thermal_sensation_min' => 'nullable|numeric',
            'humidity_max' => 'nullable|numeric',
            'humidity_min' => 'nullable|numeric',
            'uv_max' => 'nullable|integer|min:0|max:20',
            'elaborated_at' => 'required|date',
        ]);
    }

    public static function isValid(array $data): bool
    {
        return ! self::validation($data)->fails();
    }

    /**
     * Recibe las filas ya parseadas (una por día, hasta 7 por sondeo) y las
     * guarda. Un sondeo repetido sobre el mismo día actualiza, no duplica.
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
                ['date' => $row['date']],
                self::validation($row)->validated(),
            );
        }

        return $result;
    }
}
