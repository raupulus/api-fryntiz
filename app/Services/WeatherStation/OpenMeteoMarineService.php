<?php

declare(strict_types=1);

namespace App\Services\WeatherStation;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Petición a Open-Meteo Marine (`sea_level_height_msl` horario). Servicio
 * público sin API key: sin los dos saltos ni la cuota de AEMET, sólo un GET.
 */
class OpenMeteoMarineService
{
    /**
     * @return array{times: array<int,string>, heights: array<int,float>, generated_at: Carbon}|null
     */
    public function fetchSeaLevelHeights(): ?array
    {
        $reference = config('open_meteo_marine.reference');

        try {
            $response = Http::timeout((int) config('open_meteo_marine.timeout_seconds', 15))
                ->retry(2, 2000, function (\Throwable $exception) {
                    if ($exception instanceof RequestException) {
                        $status = $exception->response?->status();

                        return $status !== null && $status >= 500 && $status < 600;
                    }

                    return $exception instanceof ConnectionException;
                }, throw: false)
                ->get(config('open_meteo_marine.base_url'), [
                    'latitude' => $reference['lat'],
                    'longitude' => $reference['lon'],
                    'hourly' => 'sea_level_height_msl',
                    'forecast_days' => config('open_meteo_marine.forecast_days'),
                    'timezone' => config('open_meteo_marine.timezone'),
                ]);
        } catch (\Throwable $e) {
            Log::error('Open-Meteo Marine: excepción al pedir la altura del mar', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Open-Meteo Marine: respuesta no exitosa', [
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();
        $times = $data['hourly']['time'] ?? null;
        $heights = $data['hourly']['sea_level_height_msl'] ?? null;

        if (! is_array($times) || ! is_array($heights) || count($times) !== count($heights) || count($times) < 3) {
            Log::warning('Open-Meteo Marine: respuesta con forma inesperada', [
                'has_times' => is_array($times),
                'has_heights' => is_array($heights),
            ]);

            return null;
        }

        return [
            'times' => $times,
            'heights' => array_map('floatval', $heights),
            'generated_at' => now(),
        ];
    }
}
