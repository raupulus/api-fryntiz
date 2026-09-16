<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\OpenMeteo;

use App\Enums\TideExtremeTypeEnum;
use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Un extremo de marea (pleamar o bajamar) de Chipiona, calculado por
 * {@see \App\Support\WeatherStation\TideExtremesCalculator} sobre la serie
 * horaria de Open-Meteo Marine.
 *
 * @property int $id
 * @property TideExtremeTypeEnum $type
 * @property Carbon $happens_at
 * @property float $height_m
 * @property Carbon $forecast_generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|OpenMeteoMarineTide upcoming()
 * @method static Builder<static>|OpenMeteoMarineTide newModelQuery()
 * @method static Builder<static>|OpenMeteoMarineTide newQuery()
 * @method static Builder<static>|OpenMeteoMarineTide query()
 *
 * @mixin \Eloquent
 */
class OpenMeteoMarineTide extends BaseModel
{
    use HasFactory;

    protected $table = 'open_meteo_marine_tides';

    protected $fillable = [
        'type',
        'happens_at',
        'height_m',
        'forecast_generated_at',
    ];

    protected $casts = [
        'type' => TideExtremeTypeEnum::class,
        'happens_at' => 'datetime',
        'height_m' => 'float',
        'forecast_generated_at' => 'datetime',
    ];

    /**
     * Los que todavía no han pasado.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('happens_at', '>=', now());
    }

    /**
     * Guarda los extremos calculados por {@see \App\Support\WeatherStation\TideExtremesCalculator::extremes()}.
     *
     * Clave natural: `happens_at` redondeado al minuto. Dos sondeos con
     * ventanas de predicción solapadas producen el mismo extremo con una
     * diferencia de segundos despreciable; sin este `updateOrCreate` se
     * acumularía un duplicado casi idéntico en cada sondeo.
     *
     * @param  array<int,array{type:TideExtremeTypeEnum,happens_at:Carbon,height_m:float}>  $extremes
     * @return array<int,self>
     */
    public static function saveExtremes(array $extremes, Carbon $forecastGeneratedAt): array
    {
        $result = [];

        foreach ($extremes as $extreme) {
            $result[] = self::updateOrCreate(
                ['happens_at' => $extreme['happens_at']->copy()->second(0)],
                [
                    'type' => $extreme['type']->value,
                    'height_m' => $extreme['height_m'],
                    'forecast_generated_at' => $forecastGeneratedAt,
                ],
            );
        }

        return $result;
    }
}
