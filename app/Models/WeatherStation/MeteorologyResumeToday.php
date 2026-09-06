<?php

declare(strict_types=1);

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Traits\BelongsToHardwareDevice;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Resumen meteorológico del día actual.
 *
 * @property int $id
 * @property int|null $hardware_device_id Dispositivo asociado
 * @property numeric|null $air_quality Resultado del algoritmo para calcular porcentaje de calidad del aire según resistencia, medida en frio y compensación por humedad
 * @property numeric|null $eco2 Partículas en el aire. Valor entre 400ppm y 8192ppm
 * @property numeric|null $humidity Humedad relativa. Valor entre 0 y 100
 * @property numeric|null $light Índice de luz
 * @property numeric|null $pressure Presión atmosférica.
 * @property numeric|null $temperature Temperatura en grados centígrados.
 * @property numeric|null $tvoc Volatilidad tóxica. Valor entre  0ppb y 1187ppb
 * @property numeric|null $uv_index Índice ultravioleta. Valor entre 0 y 11
 * @property numeric|null $uva Índice ultravioleta. Valor entre 0 y 11
 * @property numeric|null $uvb Índice ultravioleta. Valor entre 0 y 11
 * @property numeric|null $wind_direction Dirección del viento (N, S, E, O, NE, NO, SE, SO)
 * @property numeric|null $wind_speed Velocidad del viento en km/h
 * @property numeric|null $wind_speed_max Racha máxima del viento en km/h
 * @property numeric|null $wind_speed_min Racha mínima del viento en km/h
 * @property int|null $lightning Cantidad de rayos
 * @property numeric|null $lightning_distance Distancia media de los rayos
 * @property numeric|null $lightning_intensity Intensidad media de los rayos
 * @property numeric|null $rain Lluvia acumulada en mm
 * @property numeric|null $rain_intensity Intensidad de la lluvia en mm/h
 * @property string|null $created_at
 * @property-read HardwareDevice|null $hardwareDevice
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereAirQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereEco2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereLight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereLightning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereLightningDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereLightningIntensity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereRain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereRainIntensity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereTvoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereUvIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereUva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereUvb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereWindDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereWindSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereWindSpeedMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday whereWindSpeedMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeToday forDevice(int $deviceId)
 *
 * @mixin \Eloquent
 */
class MeteorologyResumeToday extends BaseModel
{
    use BelongsToHardwareDevice;

    protected $table = 'meteorology_resume_today';

    public $timestamps = false;

    protected $fillable = [
        'hardware_device_id',
        'air_quality', 'eco2', 'humidity', 'light', 'pressure',
        'temperature', 'tvoc', 'uv_index', 'uva', 'uvb',
        'wind_direction', 'wind_speed', 'wind_speed_max', 'wind_speed_min',
        'lightning', 'lightning_distance', 'lightning_intensity',
        'rain', 'rain_intensity',
        'created_at',
    ];

    /**
     * El dueño de un resumen es el de la estación que lo generó.
     *
     * Antes salía de una columna `user_id` propia, que era el mismo dato
     * duplicado en cada fila: se retiró en la migración
     * `2026_09_06_000002_drop_user_id_from_sensor_tables`.
     */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            HardwareDevice::class,
            'id',
            'id',
            'hardware_device_id',
            'user_id'
        );
    }
}
