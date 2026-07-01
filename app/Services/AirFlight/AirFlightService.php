<?php

declare(strict_types=1);

namespace App\Services\AirFlight;

use App\Models\AirFlight\AirFlightAirPlane;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio encargado de procesar y almacenar la telemetría y detección de vuelos (ADSB).
 */
class AirFlightService
{
    /**
     * Registra un nuevo avión o detección de vuelo individual en el sistema.
     *
     * @param  array  $data  Datos capturados del avión.
     * @return AirFlightAirPlane Instancia del modelo guardado.
     */
    public function addAircraft(array $data): AirFlightAirPlane
    {
        return AirFlightAirPlane::create($data);
    }

    /**
     * Procesa y registra en lote múltiples detecciones de aviones enviadas por el nodo ADSB.
     *
     * @param  array  $records  Arreglo multidimensional con los datos de múltiples aviones.
     * @return array Lista de modelos almacenados.
     */
    public function addAircraftBatch(array $records): array
    {
        $stored = [];
        foreach ($records as $record) {
            $stored[] = AirFlightAirPlane::create($record);
        }

        return $stored;
    }

    /**
     * Obtiene el historial paginado de aviones detectados junto con sus rutas asociadas.
     *
     * @param  int  $perPage  Cantidad de registros por página (por defecto 50).
     * @return LengthAwarePaginator Paginador con el historial de vuelos.
     */
    public function getAircraftHistory(int $perPage = 50): LengthAwarePaginator
    {
        return AirFlightAirPlane::with('latestRoute')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Aviones vistos en los últimos minutos, con su última posición conocida.
     * Es la fuente de datos que consume el mapa en vivo.
     *
     * @param  int  $minutes  Ventana de actividad reciente (por defecto 10 minutos).
     */
    public function getActiveAircrafts(int $minutes = 10): Collection
    {
        return AirFlightAirPlane::with(['latestRoute', 'trail'])
            ->where('seen_last_at', '>=', now()->subMinutes($minutes))
            ->orderByDesc('seen_last_at')
            ->get();
    }
}
