<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\WeatherStation;

use App\Models\WeatherStation\Wind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializa una estación meteorológica con sus últimas lecturas ya formateadas
 * y listas para usar: valores numéricos (nunca cadenas con unidad), viento en
 * km/h, temperaturas y magnitudes redondeadas a 2 decimales.
 *
 * Acepta el parámetro `sensors` (lista separada por comas) para devolver solo
 * los sensores solicitados; si no se indica, se devuelven todos.
 *
 * El recurso envuelve el array que produce
 * `WeatherStationService::getStationReadings()`.
 */
class WeatherStationResource extends JsonResource
{
    /**
     * Sensores seleccionables mediante el parámetro `sensors`.
     */
    public const SENSORS = [
        'temperature',
        'humidity',
        'pressure',
        'wind',
        'light',
        'air_quality',
        'rain',
        'lightning',
    ];

    public function toArray(Request $request): array
    {
        $data = $this->resource;
        $station = $data['station'];
        $requested = self::requestedSensors($request);

        $payload = [
            'id' => $station->getKey(),
            'name' => $station->display_name,
            'zone' => $station->zone,
            'location_type' => $station->location_type?->value,
            'location_label' => $station->location_label,
            'instant' => $data['instant'],
        ];

        $blocks = [
            'temperature' => fn () => $this->toFloat($data['temperature']),
            'humidity' => fn () => $this->toFloat($data['humidity']),
            'pressure' => fn () => $this->toFloat($data['pressure']),
            'wind' => fn () => [
                'average' => Wind::msToKmh($data['wind']['average']),
                'min' => Wind::msToKmh($data['wind']['min']),
                'max' => Wind::msToKmh($data['wind']['max']),
                'direction' => $data['wind']['direction'],
                'direction_grades' => $this->toInt($data['wind']['grades']),
            ],
            'light' => fn () => [
                'lux' => $this->toFloat($data['light']['lux']),
                'uv_index' => $this->toFloat($data['light']['uv_index']),
                'uva' => $this->toFloat($data['light']['uva']),
                'uvb' => $this->toFloat($data['light']['uvb']),
            ],
            'air_quality' => fn () => [
                'quality' => $this->toFloat($data['air_quality']['quality']),
                'eco2' => $this->toInt($data['air_quality']['eco2']),
                'tvoc' => $this->toInt($data['air_quality']['tvoc']),
            ],
            'rain' => fn () => [
                'value' => $this->toFloat($data['rain']['value']),
                'intensity' => $this->toFloat($data['rain']['intensity']),
            ],
            'lightning' => fn () => [
                'last_at' => $data['lightning']['last_at']?->toISOString(),
                'last_six_hours' => (int) $data['lightning']['last_six_hours'],
                'distance' => $this->toFloat($data['lightning']['distance']),
                'energy' => $this->toInt($data['lightning']['energy']),
            ],
        ];

        foreach ($requested as $sensor) {
            $payload[$sensor] = $blocks[$sensor]();
        }

        return $payload;
    }

    /**
     * Determina qué sensores se piden a partir del parámetro `sensors`.
     * Devuelve la lista canónica (todos) si no se indica ninguno válido.
     *
     * @return array<int, string>
     */
    public static function requestedSensors(Request $request): array
    {
        $sensors = $request->query('sensors');

        if ($sensors === null || $sensors === '') {
            return self::SENSORS;
        }

        $list = is_array($sensors) ? $sensors : explode(',', (string) $sensors);
        $list = array_map(static fn ($s) => mb_strtolower(trim((string) $s)), $list);

        $valid = array_values(array_intersect(self::SENSORS, $list));

        return $valid ?: self::SENSORS;
    }

    /**
     * Redondea a 2 decimales como número, o null si no es numérico.
     */
    private function toFloat(mixed $value): ?float
    {
        return $value === null || ! is_numeric($value) ? null : round((float) $value, 2);
    }

    /**
     * Devuelve un entero, o null si no es numérico.
     */
    private function toInt(mixed $value): ?int
    {
        return $value === null || ! is_numeric($value) ? null : (int) round((float) $value);
    }
}
