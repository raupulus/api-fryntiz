<?php

declare(strict_types=1);

namespace App\Events\WeatherStation;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * Una estación acaba de subir lecturas.
 *
 * # Por qué hay UN evento y no nueve
 *
 * Antes había nueve —`TemperatureUpdateEvent`, `HumidityUpdateEvent`…—, todos
 * el mismo fichero con otro nombre y **todos emitiendo al mismo canal**
 * (`weather-station`), así que la separación no servía ni para suscribirse a un
 * sensor suelto: el cliente recibía los nueve igual y filtraba por el nombre
 * del evento. Y colgaban de `$dispatchesEvents['created']` de cada modelo,
 * que el camino de escritura de la API **no dispara**: se inserta el lote
 * entero con `insert()` del query builder, que no pasa por Eloquent.
 *
 * O sea: nueve clases que no se emitieron nunca. Ahora hay una, se emite
 * explícitamente desde el controlador, y una petición con once sensores es
 * **un** mensaje en vez de once.
 *
 * # Qué lleva dentro
 *
 * Lo que se acaba de insertar, tal cual, agrupado por sensor. No se vuelve a
 * consultar la base de datos para rehacer la foto completa de la estación:
 * una estación sube cada pocos segundos y eso serían doce consultas por
 * escritura. El cliente ya tiene el resto del estado, esto le dice qué cambió.
 *
 * # Canal
 *
 * `weather-station.{id}`, público: estas lecturas se sirven sin autenticar por
 * la API, así que exigir un token para escucharlas no protegería nada. El id
 * de la estación principal sale de `GET /api/v2/weather-stations`.
 */
final class ReadingsReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /**
     * @param  int  $station  `hardware_device_id` de la estación.
     * @param  array<string, array<int, array<string, mixed>>>  $sensors  Segmento => filas insertadas.
     */
    public function __construct(
        public readonly int $station,
        public readonly array $sensors,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('weather-station.'.$this->station);
    }

    /**
     * Nombre estable del evento en el cliente.
     *
     * Sin esto Echo escucharía por el FQCN de la clase, y renombrar o mover la
     * clase rompería a los ocho sitios que consumen esta API sin que nada aquí
     * lo delate.
     */
    public function broadcastAs(): string
    {
        return 'readings.received';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'station_id' => $this->station,
            'sensors' => $this->sensors,
            'at' => Carbon::now()->toISOString(),
        ];
    }
}
