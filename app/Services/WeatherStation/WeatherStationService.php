<?php

namespace App\Services\WeatherStation;

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
use Illuminate\Support\Collection;

class WeatherStationService
{
    public function getResume(): array
    {
        return [
            'temperature' => Temperature::latestRecord()->first(),
            'humidity' => Humidity::latestRecord()->first(),
            'pressure' => Pressure::latestRecord()->first(),
            'light' => Light::latestRecord()->first(),
            'air_quality' => AirQuality::latestRecord()->first(),
        ];
    }

    public function getPreparedData(string $sensorType, ?string $from = null, ?string $to = null): Collection
    {
        $model = $this->resolveSensorModel($sensorType);
        $query = $model::query()->orderBy('created_at', 'desc');

        if ($from && $to) {
            $query->betweenDates($from, $to);
        } else {
            $query->lastDays(7);
        }

        return $query->get();
    }

    public function storeGenericData(array $data, int $hardwareDeviceId): array
    {
        $stored = [];
        foreach ($data as $sensorType => $values) {
            $model = $this->resolveSensorModel($sensorType);
            if ($model && is_array($values)) {
                foreach ($values as $record) {
                    $record['hardware_device_id'] = $hardwareDeviceId;
                    $stored[] = $model::create($record);
                }
            }
        }

        return $stored;
    }

    private function resolveSensorModel(string $type): ?string
    {
        $map = [
            'temperature' => Temperature::class,
            'humidity' => Humidity::class,
            'pressure' => Pressure::class,
            'light' => Light::class,
            'wind' => Wind::class,
            'wind_direction' => WindDirection::class,
            'rain' => Rain::class,
            'eco2' => Eco2::class,
            'tvoc' => Tvoc::class,
            'air_quality' => AirQuality::class,
            'lightning' => Lightning::class,
        ];

        return $map[$type] ?? null;
    }
}
