<?php

declare(strict_types=1);

namespace App\Services\WeatherStation;

use App\Enums\HardwareLocationTypeEnum;
use App\Models\Hardware\HardwareDevice;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Servicio encargado de gestionar los datos recolectados por los módulos físicos de la estación meteorológica.
 */
class WeatherStationService
{
    /**
     * Resuelve la estación a consultar. Si se indica un id, devuelve esa
     * estación meteorológica; si no, la estación principal por defecto (la
     * primera de exterior, salvo que se configure otra).
     *
     * @param  int|null  $stationId  Id del dispositivo o null para la principal.
     */
    public function resolveStation(?int $stationId = null): ?HardwareDevice
    {
        if ($stationId !== null) {
            return HardwareDevice::weatherStations()->whereKey($stationId)->first();
        }

        $mainId = $this->resolveMainStationId();

        return $mainId ? HardwareDevice::find($mainId) : null;
    }

    /**
     * Devuelve las estaciones de una zona concreta, opcionalmente filtradas por
     * tipo de ubicación (interior/exterior). Siempre devuelve una colección
     * (posiblemente vacía o con una sola estación).
     *
     * @param  string  $zone  Nombre de la zona (coincidencia insensible a mayúsculas).
     * @param  string|null  $locationType  'indoor'/'outdoor' para acotar dentro de la zona.
     * @return Collection<int, HardwareDevice>
     */
    public function getZoneStations(string $zone, ?string $locationType = null): Collection
    {
        return HardwareDevice::weatherStations()
            ->whereRaw('LOWER(zone) = ?', [mb_strtolower($zone)])
            ->when($locationType, fn (Builder $q) => $q->where('location_type', $locationType))
            ->orderBy('id')
            ->get();
    }

    /**
     * Los once modelos de sensor que componen una lectura de estación, con la
     * clave que ocupan en el array de resultado.
     *
     * @var array<string, class-string<Model>>
     */
    private const SENSORES = [
        'temperature' => Temperature::class,
        'humidity' => Humidity::class,
        'pressure' => Pressure::class,
        'light' => Light::class,
        'airQuality' => AirQuality::class,
        'tvoc' => Tvoc::class,
        'eco2' => Eco2::class,
        'wind' => Wind::class,
        'windDirection' => WindDirection::class,
        'rain' => Rain::class,
        'lightning' => Lightning::class,
    ];

    /**
     * Lecturas de varias estaciones a la vez.
     *
     * `getStationReadings()` cuesta doce consultas por estación (once sensores
     * más el recuento de rayos), así que pedir una zona entera multiplicaba ese
     * doce por el número de estaciones (API-06). Aquí se da la vuelta: una
     * consulta por SENSOR, no por estación, resolviendo la última fila de cada
     * dispositivo con `DISTINCT ON` —de PostgreSQL, que es la base de datos de
     * este proyecto en local, testing y producción— y repartiendo después en
     * memoria. Con eso el coste deja de crecer con el número de estaciones.
     *
     * @param  Collection<int, HardwareDevice>  $stations
     * @return array<int, array<string, mixed>> Una lectura por estación, en el mismo orden.
     */
    public function getStationsReadings(Collection $stations): array
    {
        if ($stations->isEmpty()) {
            return [];
        }

        $stationIds = $stations->map(fn (HardwareDevice $station) => $station->getKey())->all();

        // # Última fila de cada sensor para cada estación, indexada por id de
        // dispositivo: once consultas en total, sean dos estaciones o veinte.
        $ultimas = [];

        foreach (self::SENSORES as $clave => $modelo) {
            $ultimas[$clave] = $this->latestPerStation($modelo, $stationIds);
        }

        $minutosDeRayos = (int) config('weather_station.lightning_window_minutes', 60);

        // # El recuento de rayos de la ventana, agrupado en una sola consulta.
        $conteoDeRayos = Lightning::query()
            ->selectRaw('hardware_device_id, COUNT(*) AS total')
            ->where('created_at', '>=', now()->subMinutes($minutosDeRayos))
            ->whereIn('hardware_device_id', $stationIds)
            ->groupBy('hardware_device_id')
            ->pluck('total', 'hardware_device_id');

        return $stations->map(function (HardwareDevice $station) use ($ultimas, $conteoDeRayos, $minutosDeRayos): array {
            $id = $station->getKey();

            $lecturas = [];

            foreach (array_keys(self::SENSORES) as $clave) {
                $lecturas[$clave] = $ultimas[$clave][$id] ?? null;
            }

            return $this->buildReadings(
                $station,
                $lecturas,
                (int) ($conteoDeRayos[$id] ?? 0),
                $minutosDeRayos
            );
        })->all();
    }

    /**
     * Última fila de un modelo de sensor para cada una de las estaciones dadas.
     *
     * `DISTINCT ON (hardware_device_id)` con el orden adecuado devuelve
     * directamente la primera fila de cada grupo, que es justo la más reciente.
     * Es específico de PostgreSQL y evita el self-join contra el máximo de
     * `created_at` que haría falta en otros motores.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, int|string|null>  $stationIds
     * @return array<int|string, Model>
     */
    private function latestPerStation(string $modelClass, array $stationIds): array
    {
        return $modelClass::query()
            ->whereIn('hardware_device_id', $stationIds)
            ->selectRaw('DISTINCT ON (hardware_device_id) *')
            ->orderBy('hardware_device_id')
            ->orderByDesc('created_at')
            ->get()
            ->keyBy('hardware_device_id')
            ->all();
    }

    /**
     * Obtiene las últimas lecturas crudas de todos los sensores de una estación.
     * El formateo (unidades, redondeo) lo aplica el WeatherStationResource; aquí
     * solo se recopilan los valores tal como están almacenados.
     *
     * @return array Estructura con la estación, el instante y los valores crudos por sensor.
     */
    public function getStationReadings(HardwareDevice $station): array
    {
        $stationId = $station->getKey();

        $lecturas = [];

        foreach (self::SENSORES as $clave => $modelo) {
            $lecturas[$clave] = $this->latestFor($modelo, $stationId);
        }

        // Ventana configurable (C3): v1 contaba 10 minutos, v2 seis horas. Por
        // defecto una hora, parametrizable.
        $minutosDeRayos = (int) config('weather_station.lightning_window_minutes', 60);

        $lightningCount = Lightning::where('created_at', '>=', now()->subMinutes($minutosDeRayos))
            ->where('hardware_device_id', $stationId)
            ->count();

        return $this->buildReadings($station, $lecturas, $lightningCount, $minutosDeRayos);
    }

    /**
     * Da forma al array de lectura a partir de los registros ya resueltos.
     *
     * Lo comparten la vía de una estación y la de varias, para que la forma de
     * la respuesta salga de un único sitio: si se añade un sensor, se añade
     * aquí y las dos vías lo devuelven igual.
     *
     * @param  array<string, mixed>  $lecturas  Último registro de cada sensor, o null.
     * @return array<string, mixed>
     */
    private function buildReadings(HardwareDevice $station, array $lecturas, int $lightningCount, int $minutosDeRayos): array
    {
        $now = now();
        $hour = (int) $now->format('H');

        $temperature = $lecturas['temperature'] ?? null;
        $humidity = $lecturas['humidity'] ?? null;
        $pressure = $lecturas['pressure'] ?? null;
        $light = $lecturas['light'] ?? null;
        $airQuality = $lecturas['airQuality'] ?? null;
        $tvoc = $lecturas['tvoc'] ?? null;
        $eco2 = $lecturas['eco2'] ?? null;
        $wind = $lecturas['wind'] ?? null;
        $windDirection = $lecturas['windDirection'] ?? null;
        $rain = $lecturas['rain'] ?? null;
        $lastLightning = $lecturas['lightning'] ?? null;

        return [
            'station' => $station,
            'instant' => [
                'day_name' => $now->locale('es')->dayName,
                'date_human_format' => $now->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
                'time' => $now->format('H:i'),
                'day_status' => ($hour >= 7 && $hour < 21) ? 'Día' : 'Noche',
            ],
            'temperature' => $temperature?->value,
            'humidity' => $humidity?->value,
            'pressure' => $pressure?->value,
            'wind' => [
                'average' => $wind?->average,
                'min' => $wind?->min,
                'max' => $wind?->max,
                'direction' => $windDirection?->direction,
                'grades' => $windDirection?->grades,
            ],
            'light' => [
                'lux' => $light?->lux,
                'uv_index' => $light?->index,
                'uva' => $light?->uva,
                'uvb' => $light?->uvb,
            ],
            'air_quality' => [
                'quality' => $airQuality?->air_quality,
                'eco2' => $eco2?->value,
                'tvoc' => $tvoc?->value,
            ],
            'rain' => [
                'value' => $rain?->rain,
                'intensity' => $rain?->rain_intensity,
            ],
            'lightning' => [
                'last_at' => $lastLightning?->created_at,
                'window_minutes' => $minutosDeRayos,
                'count_in_window' => $lightningCount,
                'distance' => $lastLightning?->distance,
                'energy' => $lastLightning?->energy,
            ],
        ];
    }

    /**
     * Resuelve el identificador de la estación principal por defecto.
     *
     * Orden de resolución: valor de configuración `weather_station.main_station_id`
     * (si existe y es válido) → primera estación de exterior → cualquier estación.
     */
    public function resolveMainStationId(): ?int
    {
        $configured = config('weather_station.main_station_id');

        if ($configured && HardwareDevice::whereKey($configured)->exists()) {
            return (int) $configured;
        }

        $outdoor = HardwareDevice::weatherStations()
            ->where('location_type', HardwareLocationTypeEnum::Outdoor)
            ->orderBy('id')
            ->value('id');

        if ($outdoor) {
            return (int) $outdoor;
        }

        $any = HardwareDevice::weatherStations()->orderBy('id')->value('id');

        return $any ? (int) $any : null;
    }

    /**
     * Última lectura de un modelo de sensor, opcionalmente filtrada por estación.
     *
     * @param  class-string  $modelClass  Modelo de sensor (subclase de BaseWeatherStation).
     */
    private function latestFor(string $modelClass, ?int $stationId)
    {
        return $modelClass::latestRecord()
            ->when($stationId, fn (Builder $q) => $q->where('hardware_device_id', $stationId))
            ->first();
    }
}
