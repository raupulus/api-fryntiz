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
     * Obtiene las últimas lecturas crudas de todos los sensores de una estación.
     * El formateo (unidades, redondeo) lo aplica el WeatherStationResource; aquí
     * solo se recopilan los valores tal como están almacenados.
     *
     * @return array Estructura con la estación, el instante y los valores crudos por sensor.
     */
    public function getStationReadings(HardwareDevice $station): array
    {
        $stationId = $station->getKey();

        $temperature = $this->latestFor(Temperature::class, $stationId);
        $humidity = $this->latestFor(Humidity::class, $stationId);
        $pressure = $this->latestFor(Pressure::class, $stationId);
        $light = $this->latestFor(Light::class, $stationId);
        $airQuality = $this->latestFor(AirQuality::class, $stationId);
        $tvoc = $this->latestFor(Tvoc::class, $stationId);
        $eco2 = $this->latestFor(Eco2::class, $stationId);
        $wind = $this->latestFor(Wind::class, $stationId);
        $windDirection = $this->latestFor(WindDirection::class, $stationId);
        $rain = $this->latestFor(Rain::class, $stationId);
        $lastLightning = $this->latestFor(Lightning::class, $stationId);

        $now = now();
        $hour = (int) $now->format('H');

        // Ventana configurable (C3): v1 contaba 10 minutos, v2 seis horas. Por
        // defecto una hora, parametrizable.
        $minutosDeRayos = (int) config('weather_station.lightning_window_minutes', 60);

        $lightningCount = Lightning::where('created_at', '>=', now()->subMinutes($minutosDeRayos))
            ->where('hardware_device_id', $stationId)
            ->count();

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
