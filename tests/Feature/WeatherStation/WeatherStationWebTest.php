<?php

declare(strict_types=1);

namespace Tests\Feature\WeatherStation;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Wind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeatherStationWebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un dispositivo de tipo "Estación Meteorológica".
     */
    private function makeStation(string $locationType, string $zone, string $name): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'name' => $name,
            'location_type' => $locationType,
            'zone' => $zone,
        ]);
    }

    #[Test]
    public function index_only_shows_cards_for_sensors_with_data(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 25.0, 'created_at' => now()]);

        $response = $this->get(route('weather_station.index'));

        $response->assertOk();
        // La tarjeta de temperatura (con datos) enlaza a su vista de detalle.
        $response->assertSee(route('weather_station.sensor', ['type' => 'temperature', 'station' => $station->id]));
        // La de humedad (sin datos) no debe generarse.
        $response->assertDontSee(route('weather_station.sensor', ['type' => 'humidity', 'station' => $station->id]));
    }

    #[Test]
    public function sensor_view_shows_station_name(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Estación Azotea');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 25.0, 'created_at' => now()]);

        $response = $this->get(route('weather_station.sensor', ['type' => 'temperature', 'station' => $station->id]));

        $response->assertOk();
        $response->assertSee('Estación Azotea', false);
    }

    #[Test]
    public function sensor_without_data_redirects_to_index(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');
        // Sin registros de humedad para esta estación.

        $response = $this->get(route('weather_station.sensor', ['type' => 'humidity', 'station' => $station->id]));

        $response->assertRedirect(route('weather_station.index'));
    }

    #[Test]
    public function next_sensor_skips_types_without_data_for_station(): void
    {
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 25.0, 'created_at' => now()]);
        Wind::create([
            'hardware_device_id' => $station->id,
            'speed' => 5.0, 'average' => 5.0, 'min' => 2.0, 'max' => 8.0,
            'created_at' => now(),
        ]);
        // humidity y pressure (siguientes en el mapa) sin datos: debe saltarlos y llegar a wind.

        $response = $this->get(route('weather_station.sensor', ['type' => 'temperature', 'station' => $station->id]));

        $response->assertOk();
        $response->assertSee('Siguiente: Viento');
    }

    #[Test]
    public function unclassified_hardware_is_ignored_on_index(): void
    {
        // Estación real, para que el índice agrupe por estaciones (no caiga
        // en el modo de reserva global sin clasificar).
        $station = $this->makeStation('outdoor', 'Azotea', 'Azotea');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 25.0, 'created_at' => now()]);

        // Hardware que NO es estación meteorológica, con su propio dato de humedad.
        $type = HardwareType::create(['name' => 'PC Portátil']);
        $device = HardwareDevice::create(['hardware_type_id' => $type->id, 'name' => 'Portátil']);
        Humidity::create(['hardware_device_id' => $device->id, 'value' => 50.0, 'created_at' => now()]);

        $response = $this->get(route('weather_station.index'));

        $response->assertOk();
        $response->assertDontSee(route('weather_station.sensor', ['type' => 'humidity', 'station' => $device->id]));
    }
}
