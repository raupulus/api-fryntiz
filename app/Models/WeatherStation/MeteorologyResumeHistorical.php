<?php

namespace App\Models\WeatherStation;

use App\Models\BaseModels\BaseModel;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Resumen meteorológico histórico (un registro por día).
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
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
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereAirQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereEco2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereHardwareDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereHumidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereLight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereLightning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereLightningDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereLightningIntensity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical wherePressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereRain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereRainIntensity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereTemperature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereTvoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereUvIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereUva($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereUvb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereWindDirection($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereWindSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereWindSpeedMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MeteorologyResumeHistorical whereWindSpeedMin($value)
 * @mixin \Eloquent
 */
class MeteorologyResumeHistorical extends BaseModel
{
    protected $table = 'meteorology_resume_historical';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'hardware_device_id',
        'air_quality', 'eco2', 'humidity', 'light', 'pressure',
        'temperature', 'tvoc', 'uv_index', 'uva', 'uvb',
        'wind_direction', 'wind_speed', 'wind_speed_max', 'wind_speed_min',
        'lightning', 'lightning_distance', 'lightning_intensity',
        'rain', 'rain_intensity',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hardwareDevice(): BelongsTo
    {
        return $this->belongsTo(HardwareDevice::class);
    }
}
