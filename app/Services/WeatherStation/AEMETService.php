<?php

namespace App\Services\WeatherStation;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AEMETService
{
    private string $apiKey;
    private string $baseUrl = 'https://opendata.aemet.es/opendata/api';

    public function __construct()
    {
        $this->apiKey = config('services.aemet.api_key', '');
    }

    public function getDailyPrediction(string $municipioCode): ?array
    {
        return $this->makeRequest("/prediccion/especifica/municipio/diaria/{$municipioCode}");
    }

    public function getContamination(): ?array
    {
        return $this->makeRequest('/red/especial/contaminacionfondo');
    }

    public function getAdverseEvents(): ?array
    {
        return $this->makeRequest('/avisos_cap/ultimoelaborado/area/61');
    }

    private function makeRequest(string $endpoint): ?array
    {
        try {
            $response = Http::withHeaders([
                'api_key' => $this->apiKey,
            ])->get($this->baseUrl . $endpoint);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['datos'])) {
                    $dataResponse = Http::get($data['datos']);
                    return $dataResponse->json();
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error al consultar AEMET: ' . $e->getMessage());
            return null;
        }
    }
}
