<?php

declare(strict_types=1);

namespace App\Services\WeatherStation;

use App\Enums\TideExtremeTypeEnum;
use App\Models\WeatherStation\AEMET\AEMETDailyPrediction;
use App\Models\WeatherStation\AEMET\AEMETPrediction;
use App\Models\WeatherStation\AEMET\AEMETPredictionBeach;
use App\Models\WeatherStation\AEMET\AEMETStationObservation;
use App\Models\WeatherStation\AEMET\AEMETUvi;
use App\Models\WeatherStation\OpenMeteo\OpenMeteoMarineTide;
use App\Models\WeatherStation\Wind;
use App\Support\WeatherStation\MoonPhase;
use App\Support\WeatherStation\OfficialWeatherLocationCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio que construye el parte meteorológico oficial y la tabla diaria de
 * mareas a partir de los datos ya sincronizados en base de datos (AEMET,
 * Open-Meteo Marine y estaciones locales de referencia), sin realizar nuevas
 * peticiones HTTP externas por cada consulta.
 */
class OfficialWeatherService
{
    /**
     * Segundos de caché para la respuesta compilada de una localidad o provincia.
     */
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private readonly WeatherStationService $weatherStationService) {}

    /**
     * Obtiene el parte meteorológico y de mareas para una ciudad del catálogo.
     *
     * @return array<string, mixed>|null Null si la ciudad no está soportada en el catálogo.
     */
    public function getForCity(string $citySlug): ?array
    {
        $location = OfficialWeatherLocationCatalog::findCity($citySlug);

        if ($location === null) {
            return null;
        }

        return $this->rememberReport($location);
    }

    /**
     * Obtiene el parte meteorológico y de mareas para una provincia del catálogo.
     *
     * @return array<string, mixed>|null Null si la provincia no está soportada en el catálogo.
     */
    public function getForProvince(string $provinceSlug): ?array
    {
        $location = OfficialWeatherLocationCatalog::findProvince($provinceSlug);

        if ($location === null) {
            return null;
        }

        return $this->rememberReport($location);
    }

    /**
     * Cachea el informe durante 5 minutos por tipo y slug para evitar consultas
     * repetitivas cuando múltiples portales consumen el endpoint.
     *
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    private function rememberReport(array $location): array
    {
        $cacheKey = sprintf('weather:official:%s:%s:%s', $location['type'], $location['slug'], Carbon::today()->toDateString());

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn (): array => $this->buildReport($location));
    }

    /**
     * Ensambla los bloques de cabecera, meteorología y mareas del día.
     *
     * @param  array<string, mixed>  $location
     * @return array<string, mixed>
     */
    public function buildReport(array $location): array
    {
        $now = Carbon::now();
        $today = Carbon::today();

        $daily = AEMETDailyPrediction::query()
            ->whereDate('date', $today)
            ->first()
            ?? AEMETDailyPrediction::query()->latest('date')->first();

        $hourly = AEMETPrediction::query()
            ->where('start_at', '<=', $now->toDateTimeString())
            ->where('end_at', '>=', $now->toDateTimeString())
            ->latest('start_at')
            ->first()
            ?? AEMETPrediction::query()
                ->whereDate('day_start_at', $today)
                ->latest('start_at')
                ->first()
            ?? AEMETPrediction::query()->latest('start_at')->first();

        $observation = AEMETStationObservation::query()
            ->forZone((string) $location['station_zone'])
            ->latest('observed_at')
            ->first();

        $beach = AEMETPredictionBeach::query()
            ->where('beach_id', $location['beach_id'])
            ->whereDate('date', $today)
            ->first()
            ?? AEMETPredictionBeach::query()
                ->where('beach_id', $location['beach_id'])
                ->latest('id')
                ->first();

        $uvi = AEMETUvi::query()
            ->whereDate('valid_date', $today)
            ->first()
            ?? AEMETUvi::query()->latest('valid_date')->first();

        $localReadings = $this->resolveLocalReadings((string) $location['local_zone']);

        $weather = $this->buildWeatherBlock(
            location: $location,
            daily: $daily,
            hourly: $hourly,
            observation: $observation,
            beach: $beach,
            uvi: $uvi,
            localReadings: $localReadings,
        );

        $tides = $this->buildTidesBlock(
            date: $now,
            hasCorrales: (bool) $location['has_corrales'],
        );

        $updatedAt = $this->resolveLatestTimestamp($daily, $hourly, $observation, $beach) ?? $now;

        return [
            'municipality' => $location['municipality'],
            'province' => $location['province'],
            'aemet_code' => $location['aemet_code'],
            'updated_at' => $updatedAt->format('d/m/Y H:i'),
            'updated_at_iso' => $updatedAt->toISOString(),
            'weather' => $weather,
            'tides' => $tides,
        ];
    }

    /**
     * Obtiene las lecturas agregadas de las estaciones físicas locales si existen.
     *
     * @return array<string, mixed>|null
     */
    private function resolveLocalReadings(string $localZone): ?array
    {
        $readings = $this->weatherStationService->getZoneReadings($localZone, 'outdoor')
            ?? $this->weatherStationService->getZoneReadings($localZone);

        if ($readings !== null) {
            return $readings;
        }

        $mainStation = $this->weatherStationService->resolveStation();

        return $mainStation ? $this->weatherStationService->getStationReadings($mainStation) : null;
    }

    /**
     * Construye el bloque `weather` combinando datos locales, observación de AEMET y predicción.
     *
     * @param  array<string, mixed>  $location
     * @param  array<string, mixed>|null  $localReadings
     * @return array<string, mixed>
     */
    private function buildWeatherBlock(
        array $location,
        ?AEMETDailyPrediction $daily,
        ?AEMETPrediction $hourly,
        ?AEMETStationObservation $observation,
        ?AEMETPredictionBeach $beach,
        ?AEMETUvi $uvi,
        ?array $localReadings,
    ): array {
        $hasLocalTemp = isset($localReadings['temperature']) && is_numeric($localReadings['temperature']);

        $currentTemp = match (true) {
            $hasLocalTemp => round((float) $localReadings['temperature'], 1),
            $observation?->temperature !== null => round((float) $observation->temperature, 1),
            $hourly?->temperature !== null => round((float) $hourly->temperature, 1),
            default => null,
        };

        $tempMax = match (true) {
            $daily?->temperature_max !== null => round((float) $daily->temperature_max, 1),
            $beach?->temperature_max !== null => round((float) $beach->temperature_max, 1),
            $observation?->temperature_max !== null => round((float) $observation->temperature_max, 1),
            default => $currentTemp,
        };

        $tempMin = match (true) {
            $daily?->temperature_min !== null => round((float) $daily->temperature_min, 1),
            $observation?->temperature_min !== null => round((float) $observation->temperature_min, 1),
            default => $currentTemp,
        };

        $windSpeedKmh = $this->resolveWindSpeedKmh($localReadings, $observation, $daily);
        $cardinalCode = $this->resolveWindCardinalCode($localReadings, $observation, $daily);
        $windDirection = $this->formatWindDirectionLabel($cardinalCode);

        $rawSkyState = $hourly?->sky_status
            ?: $daily?->sky_status
            ?: $beach?->sky_morning_status
            ?: 'Despejado';

        $skyState = $this->enrichSkyState($rawSkyState, $cardinalCode, $windSpeedKmh);

        $rainProb = (int) ($daily->rain_prob ?? $hourly->rain_prob ?? 0);

        $uvMax = (int) ($daily->uv_max
            ?? $uvi->uv_index
            ?? $beach->uv_max
            ?? (isset($localReadings['light']['uv_index']) ? round((float) $localReadings['light']['uv_index']) : 0));

        $seaTemp = $beach?->water_temperature !== null
            ? (float) $beach->water_temperature
            : null;

        $sourcePrefix = $hasLocalTemp
            ? 'Estación Meteorológica Chipiona + AEMET OpenData'
            : 'AEMET OpenData';

        return [
            'source' => sprintf('%s (%s)', $sourcePrefix, $location['aemet_code']),
            'current_temp' => $currentTemp,
            'temp_max' => $tempMax,
            'temp_min' => $tempMin,
            'sky_state' => $skyState,
            'wind_direction' => $windDirection,
            'wind_speed_kmh' => $windSpeedKmh,
            'uv_max' => $uvMax,
            'rain_prob' => max(0, min(100, $rainProb)),
            'sea_temp' => $seaTemp,
        ];
    }

    /**
     * Resuelve la velocidad del viento en km/h priorizando la estación local,
     * luego la observación oficial de AEMET (almacenada en m/s) y por último la
     * predicción diaria (ya en km/h).
     *
     * @param  array<string, mixed>|null  $localReadings
     */
    private function resolveWindSpeedKmh(
        ?array $localReadings,
        ?AEMETStationObservation $observation,
        ?AEMETDailyPrediction $daily,
    ): float {
        if (isset($localReadings['wind']['average']) && is_numeric($localReadings['wind']['average'])) {
            return (float) (Wind::msToKmh($localReadings['wind']['average']) ?? 0.0);
        }

        if ($observation?->wind_speed !== null) {
            return (float) (Wind::msToKmh($observation->wind_speed) ?? 0.0);
        }

        if ($daily?->wind_speed !== null) {
            return round((float) $daily->wind_speed, 1);
        }

        return 0.0;
    }

    /**
     * Determina el código cardinal en español (N, NE, E, SE, S, SO, O, NO, etc.)
     * a partir de la estación local, los grados de observación de AEMET o la predicción diaria.
     *
     * @param  array<string, mixed>|null  $localReadings
     */
    private function resolveWindCardinalCode(
        ?array $localReadings,
        ?AEMETStationObservation $observation,
        ?AEMETDailyPrediction $daily,
    ): string {
        if (! empty($localReadings['wind']['direction']) && is_string($localReadings['wind']['direction'])) {
            return $this->normalizeSpanishCardinal($localReadings['wind']['direction']);
        }

        if ($observation?->wind_direction !== null) {
            return $this->degreesToSpanishCardinal((float) $observation->wind_direction);
        }

        if (! empty($daily->wind_direction)) {
            return $this->normalizeSpanishCardinal($daily->wind_direction);
        }

        return 'O';
    }

    /**
     * Normaliza siglas anglosajonas (W, SW, NW) a las españolas (O, SO, NO).
     */
    private function normalizeSpanishCardinal(string $code): string
    {
        $upper = mb_strtoupper(trim($code));

        return str_replace('W', 'O', $upper);
    }

    /**
     * Convierte grados meteorológicos (0–360) a siglas cardinales en español.
     */
    private function degreesToSpanishCardinal(float $degrees): string
    {
        $normalized = fmod($degrees, 360.0);

        if ($normalized < 0) {
            $normalized += 360.0;
        }

        $sectors = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSO', 'SO', 'OSO', 'O', 'ONO', 'NO', 'NNO'];
        $index = (int) round($normalized / 22.5) % 16;

        return $sectors[$index];
    }

    /**
     * Formatea la dirección del viento con su denominación marinera de la costa gaditana.
     */
    public function formatWindDirectionLabel(string $cardinal): string
    {
        if ($cardinal === 'C' || $cardinal === 'CALMA') {
            return 'Calma';
        }

        $windName = match ($cardinal) {
            'O', 'OSO', 'ONO' => 'Poniente',
            'E', 'ESE', 'ENE' => 'Levante',
            'N', 'NNE', 'NNO' => 'Norte',
            'S', 'SSE', 'SSO' => 'Sur',
            'SO' => 'Lebeche / Suroeste',
            'NO' => 'Norazo / Noroeste',
            'SE' => 'Sureste',
            'NE' => 'Noreste',
            default => $cardinal,
        };

        return sprintf('%s (%s)', $windName, $cardinal);
    }

    /**
     * Enriquecer la descripción del estado del cielo con el matiz de brisa si aplica.
     */
    private function enrichSkyState(string $skyStatus, string $cardinal, float $windSpeedKmh): string
    {
        $clean = trim($skyStatus);

        if ($windSpeedKmh <= 0.0 || $cardinal === 'C') {
            return $clean;
        }

        if (in_array($cardinal, ['O', 'OSO', 'ONO', 'SO'], true) && $windSpeedKmh <= 28.0) {
            return sprintf('%s con brisa atlántica de Poniente', $clean);
        }

        if (in_array($cardinal, ['E', 'ESE', 'ENE', 'SE'], true) && $windSpeedKmh >= 20.0) {
            return sprintf('%s con viento de Levante', $clean);
        }

        return $clean;
    }

    /**
     * Construye el bloque `tides` con coeficiente, indicador de aguaje, día lunar
     * y los 4 hitos diarios (2 pleamares y 2 bajamares) ordenados cronológicamente.
     *
     * @return array<string, mixed>
     */
    private function buildTidesBlock(Carbon $date, bool $hasCorrales): array
    {
        $moon = MoonPhase::forDate($date);
        $lunarDay = max(1, min(30, (int) floor($moon['age_days']) + 1));

        $dayExtremes = $this->deduplicateDayExtremes(
            OpenMeteoMarineTide::query()
                ->whereDate('happens_at', $date->toDateString())
                ->orderBy('happens_at')
                ->get()
        );

        $coefficient = $this->calculateTideCoefficient($dayExtremes, (float) $moon['age_days']);
        $isAguaje = $coefficient >= 85;
        $typeLabel = $this->resolveTideTypeLabel($coefficient, $hasCorrales);

        $events = $this->formatTideEvents($dayExtremes, $hasCorrales);

        return [
            'coefficient' => $coefficient,
            'is_aguaje' => $isAguaje,
            'type_label' => $typeLabel,
            'lunar_day' => $lunarDay,
            'events' => $events,
        ];
    }

    /**
     * Funde extremos consecutivos del mismo tipo separados por menos de 3 horas
     * (producidos por sucesivas ejecuciones de `marine:sync` cuando el modelo de
     * Open-Meteo ajusta 1 o 2 minutos el instante de una marea). En empate de
     * ventana, conserva la predicción generada más recientemente.
     *
     * @param  Collection<int, OpenMeteoMarineTide>  $extremes
     * @return Collection<int, OpenMeteoMarineTide>
     */
    private function deduplicateDayExtremes(Collection $extremes): Collection
    {
        $minMinutes = (int) round((float) config('open_meteo_marine.min_hours_between_same_type', 3) * 60);
        $merged = [];

        foreach ($extremes as $extreme) {
            $lastIndex = array_key_last($merged);
            $last = $lastIndex !== null ? $merged[$lastIndex] : null;

            if ($last !== null
                && $last->type === $extreme->type
                && abs($extreme->happens_at->diffInMinutes($last->happens_at)) < $minMinutes
            ) {
                $extremeForecastAt = $extreme->forecast_generated_at?->getTimestamp() ?? 0;
                $lastForecastAt = $last->forecast_generated_at?->getTimestamp() ?? 0;

                if ($extremeForecastAt >= $lastForecastAt) {
                    $merged[$lastIndex] = $extreme;
                }

                continue;
            }

            $merged[] = $extreme;
        }

        /** @var Collection<int, OpenMeteoMarineTide> $collection */
        $collection = new Collection(array_values($merged));

        return $collection;
    }

    /**
     * Calcula el coeficiente de marea en la escala estándar francesa/española (35 a 115).
     *
     * Si existen pleamares y bajamares registradas para el día, pondera la amplitud
     * astronómica lunar con la amplitud real prevista por Open-Meteo Marine.
     *
     * @param  Collection<int, OpenMeteoMarineTide>  $extremes
     */
    private function calculateTideCoefficient(Collection $extremes, float $moonAgeDays): int
    {
        $synodicMonth = 29.53058868;
        // El pico de marea viva en el Golfo de Cádiz se produce ~1.5 días después de la sicigia (luna nueva / llena)
        $phaseShiftDays = 1.5;
        $angle = 2 * M_PI * ($moonAgeDays - $phaseShiftDays) / ($synodicMonth / 2);
        $astronomicalFactor = (cos($angle) + 1) / 2; // 0.0 en cuadratura, 1.0 en sicigia
        $astronomicalCoeff = (int) round(40 + $astronomicalFactor * 65);

        $highs = $extremes->filter(fn (OpenMeteoMarineTide $t): bool => $t->type === TideExtremeTypeEnum::HighTide);
        $lows = $extremes->filter(fn (OpenMeteoMarineTide $t): bool => $t->type === TideExtremeTypeEnum::LowTide);

        if ($highs->isEmpty() || $lows->isEmpty()) {
            return max(35, min(115, $astronomicalCoeff));
        }

        $maxHigh = (float) $highs->max('height_m');
        $minLow = (float) $lows->min('height_m');
        $amplitude = max(0.5, $maxHigh - $minLow);

        $amplitudeCoeff = (int) round(($amplitude / OfficialWeatherLocationCatalog::EQUINOCTIAL_SPRING_RANGE_M) * 100);

        return max(35, min(115, $amplitudeCoeff));
    }

    /**
     * Devuelve la descripción legible del tipo de marea según su coeficiente.
     */
    private function resolveTideTypeLabel(int $coefficient, bool $hasCorrales): string
    {
        if ($coefficient >= 85) {
            return $hasCorrales
                ? 'Marea Viva / Aguaje Grande (Ideal para ver los corrales en bajamar)'
                : 'Marea Viva / Coeficiente Alto (Gran amplitud de marea)';
        }

        if ($coefficient >= 70) {
            return $hasCorrales
                ? 'Marea Media-Viva (Buena visibilidad de los muros de los corrales en bajamar)'
                : 'Marea Media-Viva';
        }

        if ($coefficient >= 55) {
            return 'Marea Media (Amplitud moderada en la costa)';
        }

        return $hasCorrales
            ? 'Marea Muerta / Secano (Poca amplitud; los corrales apenas descubren por completo)'
            : 'Marea Muerta (Baja amplitud de marea)';
    }

    /**
     * Formatea los extremos del día en la estructura de eventos ordenada por hora,
     * convirtiendo la cota MSL de Open-Meteo al Cero Hidrográfico local.
     *
     * @param  Collection<int, OpenMeteoMarineTide>  $extremes
     * @return array<int, array{type: string, label: string, time: string, height_m: float, corrales_note: string}>
     */
    private function formatTideEvents(Collection $extremes, bool $hasCorrales): array
    {
        $highOrder = 0;
        $lowOrder = 0;
        $events = [];

        foreach ($extremes as $extreme) {
            $isHigh = $extreme->type === TideExtremeTypeEnum::HighTide;

            if ($isHigh) {
                $highOrder++;
                $order = $highOrder;
                $type = 'pleamar';
                $label = sprintf('%d.ª Pleamar (Marea llena)', $order);
            } else {
                $lowOrder++;
                $order = $lowOrder;
                $type = 'bajamar';
                $label = sprintf('%d.ª Bajamar (Marea vacía)', $order);
            }

            // Si la altura almacenada viene en referencia MSL (puede ser negativa o < 1.8m en pleamar),
            // se traslada al Cero Hidrográfico sumando el offset oficial de la costa gaditana.
            $rawHeight = (float) $extreme->height_m;
            $chartHeight = $rawHeight < 1.80
                ? round(max(0.05, $rawHeight + OfficialWeatherLocationCatalog::HYDROGRAPHIC_ZERO_OFFSET_M), 2)
                : round($rawHeight, 2);

            $events[] = [
                'type' => $type,
                'label' => $label,
                'time' => $extreme->happens_at->format('H:i'),
                'height_m' => $chartHeight,
                'corrales_note' => $this->resolveCorralesNote($isHigh, $order, $hasCorrales),
            ];
        }

        return $events;
    }

    /**
     * Nota interpretativa sobre el estado de los corrales de pesca para cada hito.
     */
    private function resolveCorralesNote(bool $isHigh, int $order, bool $hasCorrales): string
    {
        if (! $hasCorrales) {
            return $isHigh
                ? 'Pleamar: nivel máximo de agua en el litoral.'
                : 'Bajamar: máxima amplitud de arena descubierta en playa.';
        }

        if ($isHigh) {
            return $order === 1
                ? 'Corrales cubiertos por el Atlántico.'
                : 'Entrada de pescado y marisco a los corrales.';
        }

        return $order === 1
            ? 'Momento óptimo para observar la piedra ostionera sin pisar el escaramujo.'
            : 'Descubierta la restinga de Salmedina y muros de pesca.';
    }

    /**
     * Determina la fecha de actualización más reciente entre las fuentes consultadas.
     */
    private function resolveLatestTimestamp(
        ?AEMETDailyPrediction $daily,
        ?AEMETPrediction $hourly,
        ?AEMETStationObservation $observation,
        ?AEMETPredictionBeach $beach,
    ): ?Carbon {
        $candidates = array_filter([
            $observation?->observed_at,
            $observation?->updated_at,
            $daily?->updated_at,
            $daily?->elaborated_at,
            $hourly?->updated_at,
            $beach?->updated_at,
        ]);

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (Carbon $a, Carbon $b): int => $b->getTimestamp() <=> $a->getTimestamp());

        return $candidates[0];
    }
}
