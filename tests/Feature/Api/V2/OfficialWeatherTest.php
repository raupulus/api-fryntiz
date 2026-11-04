<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Enums\TideExtremeTypeEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\WeatherStation\AEMET\AEMETDailyPrediction;
use App\Models\WeatherStation\AEMET\AEMETPredictionBeach;
use App\Models\WeatherStation\AEMET\AEMETStationObservation;
use App\Models\WeatherStation\OpenMeteo\OpenMeteoMarineTide;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Wind;
use App\Models\WeatherStation\WindDirection;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class OfficialWeatherTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    /**
     * @return array<string, string>
     */
    private function readHeaders(): array
    {
        return $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::WEATHERSTATION_READ);
    }

    #[Test]
    public function official_city_endpoint_requires_authentication(): void
    {
        $response = $this->getJson($this->apiUrl('weather-station-official/cities/chipiona'), $this->guestHeaders());

        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function official_city_endpoint_requires_weatherstation_read_ability(): void
    {
        $headers = $this->moduleHeaders($this->createAuthenticatedUser(), TokenAbilities::HARDWARE_READ);

        $response = $this->getJson($this->apiUrl('weather-station-official/cities/chipiona'), $headers);

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function official_city_chipiona_returns_complete_weather_and_tides_contract(): void
    {
        $today = Carbon::today();

        // 1. Estación física local en Chipiona
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);
        $station = HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'name' => 'Estación Azotea Chipiona',
            'location_type' => 'outdoor',
            'zone' => 'Chipiona',
        ]);

        Temperature::create([
            'hardware_device_id' => $station->id,
            'value' => 20.8,
            'created_at' => now(),
        ]);

        Wind::create([
            'hardware_device_id' => $station->id,
            'speed' => 5.0,
            'average' => 5.0, // 5 m/s = 18 km/h
            'min' => 2.0,
            'max' => 8.0,
            'created_at' => now(),
        ]);

        WindDirection::create([
            'hardware_device_id' => $station->id,
            'direction' => 'OSO',
            'grades' => 248,
            'created_at' => now(),
        ]);

        // 2. Predicción diaria oficial AEMET (11015 - Chipiona)
        AEMETDailyPrediction::create([
            'date' => $today->toDateString(),
            'sky_status' => 'Despejado',
            'sky_status_code' => '11',
            'rain_prob' => 0,
            'wind_direction' => 'OSO',
            'wind_speed' => 15.0,
            'temperature_max' => 23.0,
            'temperature_min' => 15.0,
            'uv_max' => 6,
            'elaborated_at' => now(),
        ]);

        // 3. Predicción de playa AEMET (temperatura del agua)
        AEMETPredictionBeach::create([
            'beach_id' => 1101501,
            'name' => 'Regla',
            'slug' => 'regla',
            'city_code' => 11015,
            'sky_morning_status_code' => 11,
            'sky_morning_status' => 'Despejado',
            'sky_afternoon_status_code' => 11,
            'sky_afternoon_status' => 'Despejado',
            'wind_morning_status_code' => 1,
            'wind_morning_status' => 'Flojo',
            'wind_afternoon_status_code' => 1,
            'wind_afternoon_status' => 'Flojo',
            'wave_morning_status_code' => 1,
            'wave_morning_status' => 'Débil',
            'wave_afternoon_status_code' => 1,
            'wave_afternoon_status' => 'Débil',
            'temperature_max' => 23,
            'thermal_sensation_status_code' => 1,
            'thermal_sensation_status' => 'Suave',
            'water_temperature' => 19,
            'uv_max' => 6,
            'date' => $today->toDateString(),
            'read_at' => now()->toDateTimeString(),
        ]);

        // 4. 4 hitos de marea del día en OpenMeteoMarineTide (MSL)
        OpenMeteoMarineTide::create([
            'type' => TideExtremeTypeEnum::HighTide,
            'happens_at' => $today->copy()->setTime(3, 45),
            'height_m' => 1.52, // +1.90 offset = 3.42 m
            'forecast_generated_at' => now(),
        ]);
        OpenMeteoMarineTide::create([
            'type' => TideExtremeTypeEnum::LowTide,
            'happens_at' => $today->copy()->setTime(9, 58),
            'height_m' => -1.38, // +1.90 offset = 0.52 m
            'forecast_generated_at' => now(),
        ]);
        OpenMeteoMarineTide::create([
            'type' => TideExtremeTypeEnum::HighTide,
            'happens_at' => $today->copy()->setTime(16, 10),
            'height_m' => 1.45, // +1.90 offset = 3.35 m
            'forecast_generated_at' => now(),
        ]);
        OpenMeteoMarineTide::create([
            'type' => TideExtremeTypeEnum::LowTide,
            'happens_at' => $today->copy()->setTime(22, 25),
            'height_m' => -1.29, // +1.90 offset = 0.61 m
            'forecast_generated_at' => now(),
        ]);

        $response = $this->getJson($this->apiUrl('weather-station-official/cities/chipiona'), $this->readHeaders());

        $this->assertSuccessResponse($response);

        $data = $response->json('data');
        $this->assertSame('Chipiona (Cádiz)', $data['municipality']);
        $this->assertSame('Cádiz', $data['province']);
        $this->assertSame('11015', $data['aemet_code']);
        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', $data['updated_at']);

        // Bloque weather
        $this->assertSame('Estación Meteorológica Chipiona + AEMET OpenData (11015)', $data['weather']['source']);
        $this->assertSame(20.8, $data['weather']['current_temp']);
        $this->assertEquals(23, $data['weather']['temp_max']);
        $this->assertEquals(15, $data['weather']['temp_min']);
        $this->assertSame('Despejado con brisa atlántica de Poniente', $data['weather']['sky_state']);
        $this->assertSame('Poniente (OSO)', $data['weather']['wind_direction']);
        $this->assertEquals(18, $data['weather']['wind_speed_kmh']);
        $this->assertSame(6, $data['weather']['uv_max']);
        $this->assertSame(0, $data['weather']['rain_prob']);
        $this->assertEquals(19, $data['weather']['sea_temp']);

        // Bloque tides
        $this->assertGreaterThanOrEqual(85, $data['tides']['coefficient']);
        $this->assertTrue($data['tides']['is_aguaje']);
        $this->assertSame('Marea Viva / Aguaje Grande (Ideal para ver los corrales en bajamar)', $data['tides']['type_label']);
        $this->assertIsInt($data['tides']['lunar_day']);
        $this->assertCount(4, $data['tides']['events']);

        $this->assertSame([
            'type' => 'pleamar',
            'label' => '1.ª Pleamar (Marea llena)',
            'time' => '03:45',
            'height_m' => 3.42,
            'corrales_note' => 'Corrales cubiertos por el Atlántico.',
        ], $data['tides']['events'][0]);

        $this->assertSame([
            'type' => 'bajamar',
            'label' => '1.ª Bajamar (Marea vacía)',
            'time' => '09:58',
            'height_m' => 0.52,
            'corrales_note' => 'Momento óptimo para observar la piedra ostionera sin pisar el escaramujo.',
        ], $data['tides']['events'][1]);

        $this->assertSame([
            'type' => 'pleamar',
            'label' => '2.ª Pleamar (Marea llena)',
            'time' => '16:10',
            'height_m' => 3.35,
            'corrales_note' => 'Entrada de pescado y marisco a los corrales.',
        ], $data['tides']['events'][2]);

        $this->assertSame([
            'type' => 'bajamar',
            'label' => '2.ª Bajamar (Marea vacía)',
            'time' => '22:25',
            'height_m' => 0.61,
            'corrales_note' => 'Descubierta la restinga de Salmedina y muros de pesca.',
        ], $data['tides']['events'][3]);
    }

    #[Test]
    public function official_province_cadiz_returns_valid_response_and_handles_accents(): void
    {
        AEMETStationObservation::create([
            'station_zone' => 'chipiona_eca',
            'station_id' => '5906X',
            'observed_at' => now(),
            'temperature' => 21.4,
            'wind_speed' => 6.0,
            'wind_direction' => 90.0, // Este (Levante)
        ]);

        $response = $this->getJson($this->apiUrl('weather-station-official/provinces/cadiz'), $this->readHeaders());

        $this->assertSuccessResponse($response);
        $this->assertSame('Cádiz', $response->json('data.municipality'));
        $this->assertSame('11012', $response->json('data.aemet_code'));
        $this->assertSame(21.4, $response->json('data.weather.current_temp'));
        $this->assertSame('Levante (E)', $response->json('data.weather.wind_direction'));
    }

    #[Test]
    public function deduplicates_close_tide_extremes_from_successive_forecasts(): void
    {
        $today = Carbon::today();

        // Dos ejecuciones de marine:sync que ajustaron 2 minutos la misma bajamar
        OpenMeteoMarineTide::create([
            'type' => TideExtremeTypeEnum::LowTide,
            'happens_at' => $today->copy()->setTime(4, 42),
            'height_m' => -1.01,
            'forecast_generated_at' => now()->subHours(8),
        ]);
        OpenMeteoMarineTide::create([
            'type' => TideExtremeTypeEnum::LowTide,
            'happens_at' => $today->copy()->setTime(4, 44),
            'height_m' => -1.03,
            'forecast_generated_at' => now(),
        ]);

        $response = $this->getJson($this->apiUrl('weather-station-official/cities/chipiona'), $this->readHeaders());

        $this->assertSuccessResponse($response);
        $events = $response->json('data.tides.events');
        $this->assertCount(1, $events);
        $this->assertSame('04:44', $events[0]['time']);
        $this->assertSame('1.ª Bajamar (Marea vacía)', $events[0]['label']);
    }

    #[Test]
    public function unknown_city_or_province_returns_404(): void
    {
        $cityResponse = $this->getJson($this->apiUrl('weather-station-official/cities/unknown-city'), $this->readHeaders());
        $this->assertErrorResponse($cityResponse, 404);

        $provinceResponse = $this->getJson($this->apiUrl('weather-station-official/provinces/unknown-province'), $this->readHeaders());
        $this->assertErrorResponse($provinceResponse, 404);
    }
}
