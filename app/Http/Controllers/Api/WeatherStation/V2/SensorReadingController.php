<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\WeatherStation\V2;

use App\Events\WeatherStation\ReadingsReceived;
use App\Http\Api\CollectionQuery;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\WeatherStation\V2\StoreGenericRequest;
use App\Http\Requests\Api\WeatherStation\V2\StoreSensorReadingsRequest;
use App\Support\WeatherStation\SensorCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lecturas de los sensores de una estación.
 *
 * Un solo controlador para los once sensores. Antes eran doce controladores
 * casi idénticos, y el precio de esa duplicación fue concreto: la lectura no
 * paginaba ni filtraba por estación en ninguno de los doce.
 *
 *   GET  /weather-stations/{station}/temperatures   las de ESA estación
 *   POST /weather-stations/{station}/temperatures   una lectura o un lote (C2)
 *   POST /weather-stations/{station}/readings       lote multi-sensor
 *
 * El endpoint por sensor se mantiene a propósito (D33): permite emitir un token
 * que sólo pueda subir luz y radiación y no el resto.
 */
class SensorReadingController extends BaseApiController
{
    /**
     * Lecturas de un sensor de una estación, paginadas.
     *
     * Antes `GET /weatherstation/temperature` hacía un `->get()` sobre una tabla
     * de serie temporal: **todas** las temperaturas de **todas** las estaciones,
     * sin paginar. Con 2,6 millones de filas eso no es una respuesta lenta, es
     * un servidor caído.
     */
    public function index(Request $request, int $station, string $sensor): JsonResponse
    {
        $definition = SensorCatalog::bySegment($sensor);

        if ($definition === null) {
            return $this->notFoundResponse('Sensor no reconocido');
        }

        $model = $definition['modelo'];

        $collectionQuery = new CollectionQuery(
            filterable: ['created_at'],
            sortable: ['created_at', 'id'],
            defaultSortColumn: 'created_at',
        );

        $query = $model::query()->where('hardware_device_id', $station);

        // Ventana en minutos (C3). Nació para los rayos —v1 contaba 10 minutos y
        // v2 seis horas— pero vale para cualquier sensor: pedir «lo de la última
        // media hora» es lo normal en una serie temporal.
        $minutes = $request->integer('minutes');

        if ($minutes > 0) {
            $maximum = (int) config('weather_station.lightning_window_minutes_max', 10080);
            $query->where('created_at', '>=', now()->subMinutes(min($minutes, $maximum)));
        }

        return $this->paginatedResponse(
            $collectionQuery->paginate($query, $request),
            $definition['resource']
        );
    }

    /**
     * Guarda una lectura o un lote de lecturas de un sensor.
     */
    public function store(StoreSensorReadingsRequest $request, int $station, string $sensor): JsonResponse
    {
        $definition = SensorCatalog::bySegment($sensor);

        if ($definition === null) {
            return $this->notFoundResponse('Sensor no reconocido');
        }

        $model = $definition['modelo'];

        // Sin `updated_at`: las tablas `meteorology_*` de sensores sólo tienen
        // `created_at`. Son series temporales, una lectura no se corrige, se
        // añade otra. Meterlo aquí hacía que TODAS las escrituras de estación
        // fueran un 500 («column updated_at does not exist»).
        $now = now();

        $rows = array_map(
            static fn (array $reading): array => $reading + [
                'hardware_device_id' => $station,
                'created_at' => $now,
            ],
            $request->readings()
        );

        // Una sola sentencia para todo el lote: con lecturas cada pocos
        // segundos, un INSERT por fila multiplica el trabajo del servidor sin
        // dar nada a cambio.
        DB::transaction(static fn () => $model::query()->insert($rows));

        // El aviso por websocket va DESPUÉS de la transacción: si el INSERT se
        // deshace, nadie se ha enterado de una lectura que no existe.
        ReadingsReceived::dispatch($station, [$sensor => $rows]);

        return $this->createdResponse(
            ['stored' => count($rows)],
            count($rows) === 1 ? 'Lectura almacenada' : 'Lecturas almacenadas',
        );
    }

    /**
     * Lote multi-sensor: todos los sensores en una sola petición.
     *
     * Es una excepción consciente al REST puro. En REST serían once peticiones,
     * y para un microcontrolador con batería once peticiones son once veces el
     * coste de radio.
     */
    public function storeReadings(StoreGenericRequest $request, int $station): JsonResponse
    {
        $data = $request->validated('data');
        $segments = SensorCatalog::batchKeys();
        $now = now();
        $total = 0;

        /** @var array<string, array<int, array<string, mixed>>> $emitted */
        $emitted = [];

        DB::transaction(static function () use ($data, $segments, $station, $now, &$total, &$emitted) {
            foreach ($data as $key => $readings) {
                $definition = SensorCatalog::bySegment($segments[$key] ?? '');

                if ($definition === null) {
                    continue;
                }

                $allowed = array_keys($definition['reglas']);
                $model = $definition['modelo'];

                $rows = array_map(
                    static fn (array $reading): array => array_intersect_key($reading, array_flip($allowed)) + [
                        'hardware_device_id' => $station,
                        'created_at' => $now,
                    ],
                    $readings
                );

                if ($rows === []) {
                    continue;
                }

                $model::query()->insert($rows);
                $total += count($rows);
                $emitted[$segments[$key]] = $rows;
            }
        });

        // Un mensaje por petición, no uno por sensor: once sensores en una
        // subida son once lecturas nuevas, pero un solo cambio de estado.
        if ($emitted !== []) {
            ReadingsReceived::dispatch($station, $emitted);
        }

        return $this->createdResponse(['stored' => $total], 'Lecturas almacenadas');
    }
}
