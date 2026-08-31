<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

use App\Http\Resources\V2\WeatherStation\AirQualityResource;
use App\Http\Resources\V2\WeatherStation\Eco2Resource;
use App\Http\Resources\V2\WeatherStation\HumidityResource;
use App\Http\Resources\V2\WeatherStation\LightningResource;
use App\Http\Resources\V2\WeatherStation\LightResource;
use App\Http\Resources\V2\WeatherStation\PressureResource;
use App\Http\Resources\V2\WeatherStation\RainResource;
use App\Http\Resources\V2\WeatherStation\TemperatureResource;
use App\Http\Resources\V2\WeatherStation\TvocResource;
use App\Http\Resources\V2\WeatherStation\WindDirectionResource;
use App\Http\Resources\V2\WeatherStation\WindResource;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Eco2;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Light;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Rain;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;

/**
 * Los once sensores de la estación, en un solo sitio.
 *
 * Había **doce controladores** casi idénticos: cada uno con su `index()` que
 * llamaba al servicio con una cadena distinta y su `store()` que hacía lo mismo
 * con otro FormRequest. Doce sitios que tocar para cambiar una cosa, y doce
 * sitios donde olvidarse de tocarla — que es exactamente lo que pasó con la
 * paginación y con el filtro por estación.
 *
 * El segmento de la URL es plural porque es una colección: en
 * `/weather-stations/3/temperatures` las temperaturas son de la estación 3.
 * Antes la URL era `/weatherstation/temperature` y **no decía de qué estación
 * eran**: devolvía todas las de todas, mezcladas, sin paginar.
 *
 * Se mantiene un endpoint por sensor a propósito (D33): permite emitir un token
 * que sólo pueda subir luz y radiación, y no el resto.
 */
final class SensorCatalog
{
    /**
     * segmento de URL => [modelo, reglas de validación, Resource, clave del lote genérico]
     *
     * Las reglas viven aquí y en ningún otro sitio. Antes estaban por duplicado:
     * una copia en el FormRequest de cada sensor y otra dentro de
     * `StoreGenericRequest`, y las dos se podían desincronizar sin que nada
     * avisara — que es justo lo que había pasado.
     *
     * @var array<string, array{modelo: class-string, reglas: array<string, list<string>>, resource: class-string, clave: string}>
     */
    private const SENSOR_DEFINITIONS = [
        'temperatures' => [
            'modelo' => Temperature::class,
            'reglas' => ['value' => ['required', 'numeric']],
            'resource' => TemperatureResource::class,
            'clave' => 'temperature',
        ],
        'humidities' => [
            'modelo' => Humidity::class,
            'reglas' => ['value' => ['required', 'numeric']],
            'resource' => HumidityResource::class,
            'clave' => 'humidity',
        ],
        'pressures' => [
            'modelo' => Pressure::class,
            'reglas' => ['value' => ['required', 'numeric']],
            'resource' => PressureResource::class,
            'clave' => 'pressure',
        ],
        'lights' => [
            'modelo' => Light::class,
            'reglas' => [
                'lumens' => ['required', 'numeric'],
                // `lux` falta en el 6 % de las filas de producción: es opcional
                // de verdad, aunque la migración la creara NOT NULL (D114).
                'lux' => ['nullable', 'numeric'],
                'index' => ['nullable', 'numeric'],
                'uva' => ['nullable', 'numeric'],
                'uvb' => ['nullable', 'numeric'],
            ],
            'resource' => LightResource::class,
            'clave' => 'light',
        ],
        'winds' => [
            'modelo' => Wind::class,
            'reglas' => [
                'speed' => ['required', 'numeric', 'min:0'],
                'average' => ['required', 'numeric', 'min:0'],
                'min' => ['required', 'numeric', 'min:0'],
                'max' => ['required', 'numeric', 'min:0'],
            ],
            'resource' => WindResource::class,
            'clave' => 'wind',
        ],
        'wind-directions' => [
            'modelo' => WindDirection::class,
            'reglas' => [
                'direction' => ['required', 'string', 'max:10'],
                'grades' => ['required', 'numeric', 'min:0', 'max:360'],
                'resistance' => ['nullable', 'numeric'],
            ],
            'resource' => WindDirectionResource::class,
            'clave' => 'wind_direction',
        ],
        'rains' => [
            'modelo' => Rain::class,
            'reglas' => [
                'rain' => ['required', 'numeric', 'min:0'],
                'moisture' => ['required', 'numeric'],
                'rain_intensity' => ['nullable', 'numeric', 'min:0'],
                'rain_month' => ['nullable', 'numeric', 'min:0'],
            ],
            'resource' => RainResource::class,
            'clave' => 'rain',
        ],
        'eco2-readings' => [
            'modelo' => Eco2::class,
            'reglas' => ['value' => ['required', 'numeric']],
            'resource' => Eco2Resource::class,
            'clave' => 'eco2',
        ],
        'tvoc-readings' => [
            'modelo' => Tvoc::class,
            'reglas' => ['value' => ['required', 'numeric']],
            'resource' => TvocResource::class,
            'clave' => 'tvoc',
        ],
        'air-qualities' => [
            'modelo' => AirQuality::class,
            'reglas' => [
                'gas_resistance' => ['required', 'numeric'],
                'air_quality' => ['required', 'numeric'],
            ],
            'resource' => AirQualityResource::class,
            'clave' => 'air_quality',
        ],
        'lightnings' => [
            'modelo' => Lightning::class,
            'reglas' => [
                'distance' => ['required', 'numeric', 'min:0'],
                'energy' => ['required', 'numeric'],
                'noise_floor' => ['nullable', 'numeric'],
            ],
            'resource' => LightningResource::class,
            'clave' => 'lightning',
        ],
    ];

    /**
     * @return array{modelo: class-string, reglas: array<string, list<string>>, resource: class-string, clave: string}|null
     */
    public static function bySegment(string $segment): ?array
    {
        return self::SENSOR_DEFINITIONS[$segment] ?? null;
    }

    /**
     * Segmentos válidos, para la restricción de la ruta.
     *
     * @return array<int, string>
     */
    public static function segments(): array
    {
        return array_keys(self::SENSOR_DEFINITIONS);
    }

    /**
     * Expresión para `->where()` en la definición de rutas.
     */
    public static function routePattern(): string
    {
        return implode('|', array_map(
            static fn (string $segment): string => preg_quote($segment, '/'),
            self::segments()
        ));
    }

    /**
     * Clave que usa el lote multi-sensor (`POST .../readings`) para cada tipo.
     *
     * @return array<string, string> clave del payload => segmento
     */
    public static function batchKeys(): array
    {
        $keys = [];

        foreach (self::SENSOR_DEFINITIONS as $segment => $sensor) {
            $keys[$sensor['clave']] = $segment;
        }

        return $keys;
    }

    /**
     * Reglas de validación de un sensor, por su segmento de URL.
     *
     * `required` aquí significa «la columna es NOT NULL». Sin él la petición
     * pasaría la validación y reventaría en el INSERT con un 500 (N286).
     *
     * @return array<string, list<string>>
     */
    public static function rulesFor(string $segment): array
    {
        return self::SENSOR_DEFINITIONS[$segment]['reglas'] ?? [];
    }

    /**
     * Reglas por clave del lote multi-sensor.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function rulesByBatchKey(): array
    {
        $rules = [];

        foreach (self::SENSOR_DEFINITIONS as $sensor) {
            $rules[$sensor['clave']] = $sensor['reglas'];
        }

        return $rules;
    }
}
