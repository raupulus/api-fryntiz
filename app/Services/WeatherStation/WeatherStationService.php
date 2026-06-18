<?php

declare(strict_types=1);

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

/**
 * Servicio encargado de gestionar los datos recolectados por los módulos físicos de la estación meteorológica.
 */
class WeatherStationService
{
    /**
     * Obtiene un resumen consolidado con el último registro de los principales sensores climatológicos.
     *
     * @return array Arreglo asociativo con la última lectura de cada sensor clave.
     */
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

    /**
     * Obtiene los datos históricos de un tipo de sensor específico en un rango de fechas.
     * Si no se proveen fechas, se devuelven los registros de los últimos 7 días.
     *
     * @param string $sensorType Nombre identificador del tipo de sensor (ej: 'temperature', 'humidity').
     * @param string|null $from Fecha inicial en formato compatible.
     * @param string|null $to Fecha final en formato compatible.
     * @return \Illuminate\Support\Collection Colección con los datos solicitados.
     */
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

    /**
     * Almacena masivamente datos genéricos enviados por la estación física, 
     * resolviendo el modelo correspondiente en función de la clave del array enviado.
     *
     * @param array $data Datos estructurados por tipo de sensor.
     * @param int $hardwareDeviceId Identificador del dispositivo de hardware que envía la data.
     * @return array Colección con todos los modelos Eloquent almacenados en la base de datos.
     */
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

    /**
     * Resuelve y devuelve la clase de modelo Eloquent apropiada según el tipo de sensor.
     *
     * @param string $type Nombre en cadena de texto del sensor.
     * @return string|null Namespace de la clase del modelo correspondiente, o null si no existe.
     */
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
