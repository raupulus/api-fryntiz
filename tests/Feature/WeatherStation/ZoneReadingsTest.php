<?php

declare(strict_types=1);

namespace Tests\Feature\WeatherStation;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Models\WeatherStation\AirQuality;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Temperature;
use App\Models\WeatherStation\Tvoc;
use App\Services\WeatherStation\WeatherStationService;
use App\Support\Auth\TokenAbilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El resumen del clima va por ZONA, no por estación.
 *
 * El widget de portada iba atado a un dispositivo (`resolveMainStationId()`), y
 * eso se veía en cuanto ese dispositivo dejaba de subir: seguía enseñando su
 * último valor —humedad al 49 % durante días— mientras la estación de al lado,
 * en la misma azotea, subía el 20 % real. El dato bueno estaba en la base y
 * nadie lo miraba.
 *
 * La regla que fijan estas pruebas: de cada magnitud, el registro más reciente
 * de **cualquier** estación de la zona.
 */
class ZoneReadingsTest extends TestCase
{
    use RefreshDatabase;

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

    private function service(): WeatherStationService
    {
        return app(WeatherStationService::class);
    }

    #[Test]
    public function the_zone_takes_the_freshest_reading_even_from_another_station(): void
    {
        $old = $this->makeStation('outdoor', 'Azotea', 'Vieja');
        $new = $this->makeStation('outdoor', 'Azotea', 'Nueva');

        // La que se quedó muda hace días, con el valor que se veía congelado.
        Humidity::create([
            'hardware_device_id' => $old->id,
            'value' => 49.0,
            'created_at' => now()->subDays(3),
        ]);

        // La que está subiendo ahora.
        Humidity::create([
            'hardware_device_id' => $new->id,
            'value' => 20.0,
            'created_at' => now(),
        ]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertNotNull($readings);
        $this->assertSame(
            20.0,
            (float) $readings['humidity'],
            'La zona debe dar el dato fresco, no el de la estación que dejó de subir.'
        );
    }

    #[Test]
    public function each_magnitude_is_resolved_independently(): void
    {
        $a = $this->makeStation('outdoor', 'Azotea', 'A');
        $b = $this->makeStation('outdoor', 'Azotea', 'B');

        // Una tiene la temperatura más reciente; la otra, la humedad.
        Temperature::create(['hardware_device_id' => $a->id, 'value' => 30.0, 'created_at' => now()]);
        Temperature::create(['hardware_device_id' => $b->id, 'value' => 10.0, 'created_at' => now()->subHour()]);
        Humidity::create(['hardware_device_id' => $a->id, 'value' => 90.0, 'created_at' => now()->subHour()]);
        Humidity::create(['hardware_device_id' => $b->id, 'value' => 40.0, 'created_at' => now()]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(30.0, (float) $readings['temperature']);
        $this->assertSame(40.0, (float) $readings['humidity']);
    }

    #[Test]
    public function pressure_also_counts_from_an_indoor_station(): void
    {
        // El barómetro mide igual dentro que fuera y a la interperie se
        // estropea antes, así que suele vivir en un cacharro de interior.
        $outside = $this->makeStation('outdoor', 'Azotea', 'Fuera');
        $inside = $this->makeStation('indoor', 'Azotea', 'Dentro');

        Temperature::create(['hardware_device_id' => $outside->id, 'value' => 25.0, 'created_at' => now()]);
        Pressure::create(['hardware_device_id' => $inside->id, 'value' => 1013.0, 'created_at' => now()]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(
            1013.0,
            (float) $readings['pressure'],
            'La presión es la excepción: vale cualquier estación de la zona.'
        );
    }

    #[Test]
    public function air_quality_falls_back_to_an_indoor_station_when_outdoor_has_none(): void
    {
        // La calidad del aire (TVOC/CO2-ECO2) se monitoriza más fácil desde
        // dentro, así que si fuera no hay dato se cae al de interior.
        $outside = $this->makeStation('outdoor', 'Azotea', 'Fuera');
        $inside = $this->makeStation('indoor', 'Azotea', 'Dentro');

        Temperature::create(['hardware_device_id' => $outside->id, 'value' => 25.0, 'created_at' => now()]);
        AirQuality::create(['hardware_device_id' => $inside->id, 'gas_resistance' => 5000, 'air_quality' => 88.0, 'created_at' => now()]);
        Tvoc::create(['hardware_device_id' => $inside->id, 'value' => 120, 'created_at' => now()]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(88.0, (float) $readings['air_quality']['quality']);
        $this->assertSame(120, (int) $readings['air_quality']['tvoc']);
    }

    #[Test]
    public function air_quality_prefers_outdoor_over_a_fresher_indoor_reading(): void
    {
        // A diferencia de la presión, el aire de dentro no es "el mismo" que
        // el de fuera: si hay dato de exterior manda ese, aunque el de dentro
        // sea más reciente.
        $outside = $this->makeStation('outdoor', 'Azotea', 'Fuera');
        $inside = $this->makeStation('indoor', 'Azotea', 'Dentro');

        AirQuality::create(['hardware_device_id' => $outside->id, 'gas_resistance' => 5000, 'air_quality' => 95.0, 'created_at' => now()->subHour()]);
        AirQuality::create(['hardware_device_id' => $inside->id, 'gas_resistance' => 5000, 'air_quality' => 60.0, 'created_at' => now()]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(95.0, (float) $readings['air_quality']['quality']);
    }

    #[Test]
    public function other_sensors_do_not_leak_in_from_indoors(): void
    {
        $outside = $this->makeStation('outdoor', 'Azotea', 'Fuera');
        $inside = $this->makeStation('indoor', 'Azotea', 'Dentro');

        Temperature::create(['hardware_device_id' => $outside->id, 'value' => 25.0, 'created_at' => now()->subHour()]);
        // Más reciente, pero de interior: 22 grados dentro no son los de la calle.
        Temperature::create(['hardware_device_id' => $inside->id, 'value' => 22.0, 'created_at' => now()]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(25.0, (float) $readings['temperature']);
    }

    #[Test]
    public function lightning_strikes_are_counted_across_the_whole_zone(): void
    {
        $a = $this->makeStation('outdoor', 'Azotea', 'A');
        $b = $this->makeStation('outdoor', 'Azotea', 'B');

        Lightning::create(['hardware_device_id' => $a->id, 'distance' => 5, 'energy' => 100, 'created_at' => now()->subMinutes(5)]);
        Lightning::create(['hardware_device_id' => $b->id, 'distance' => 8, 'energy' => 120, 'created_at' => now()->subMinutes(10)]);

        $readings = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(2, $readings['lightning']['count_in_window']);
    }

    #[Test]
    public function a_zone_without_stations_returns_null(): void
    {
        $this->assertNull($this->service()->getZoneReadings('Inexistente'));
    }

    #[Test]
    public function the_main_zone_is_the_first_outdoor_one(): void
    {
        $this->makeStation('indoor', 'Salón', 'Interior');
        $this->makeStation('outdoor', 'Azotea', 'Exterior');

        $this->assertSame('Azotea', $this->service()->resolveMainZone());
    }

    #[Test]
    public function the_zone_endpoint_responds_with_the_freshest_reading(): void
    {
        $this->zoneWithTwoStations();

        $this->getJson(
            route('api.v2.weather_stations.zone', ['zone' => 'Azotea', 'locationType' => 'outdoor']),
            $this->readerHeaders()
        )
            ->assertOk()
            ->assertJsonPath('data.humidity', 20);
    }

    #[Test]
    public function the_endpoint_for_an_empty_zone_returns_404(): void
    {
        $this->getJson(
            route('api.v2.weather_stations.zone', ['zone' => 'Inexistente']),
            $this->readerHeaders()
        )->assertNotFound();
    }

    #[Test]
    public function the_api_endpoint_requires_read_permission(): void
    {
        $this->zoneWithTwoStations();

        $this->getJson(route('api.v2.weather_stations.zone', ['zone' => 'Azotea']))
            ->assertUnauthorized();
    }

    /**
     * El widget de la web se sirve desde el bloque web: sin token, porque no es
     * una integración sino una página propia, y con el mismo dato fresco.
     */
    #[Test]
    public function the_web_widget_gives_the_freshest_reading_without_a_token(): void
    {
        $this->zoneWithTwoStations();

        $this->getJson(
            route('weather_station.widget.zone', ['zone' => 'Azotea', 'locationType' => 'outdoor']),
            $this->sameOriginHeaders()
        )
            ->assertOk()
            ->assertJsonPath('data.humidity', 20);
    }

    #[Test]
    public function the_web_widget_for_an_empty_zone_returns_404(): void
    {
        $this->getJson(
            route('weather_station.widget.zone', ['zone' => 'Inexistente']),
            $this->sameOriginHeaders()
        )->assertNotFound();
    }

    /**
     * `EnsureRequestIsSameOrigin`: sin `Origin`/`Referer` que cuadre con el
     * propio host, es exactamente el caso que motivó la protección — copiar la
     * URL del panel de red del navegador y reutilizarla desde fuera.
     */
    #[Test]
    public function the_web_widget_rejects_a_request_without_a_matching_origin(): void
    {
        $this->zoneWithTwoStations();

        $url = route('weather_station.widget.zone', ['zone' => 'Azotea', 'locationType' => 'outdoor']);

        $this->getJson($url)->assertForbidden();
        $this->getJson($url, ['Origin' => 'https://otra-web.example'])->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function sameOriginHeaders(): array
    {
        return ['Origin' => url('/')];
    }

    /**
     * Dos estaciones en la misma azotea: una con el dato viejo y otra con el
     * bueno.
     */
    private function zoneWithTwoStations(): void
    {
        $old = $this->makeStation('outdoor', 'Azotea', 'Vieja');
        $new = $this->makeStation('outdoor', 'Azotea', 'Nueva');

        Humidity::create(['hardware_device_id' => $old->id, 'value' => 49.0, 'created_at' => now()->subDays(3)]);
        Humidity::create(['hardware_device_id' => $new->id, 'value' => 20.0, 'created_at' => now()]);
    }

    /**
     * Cabeceras de un cliente de la API con permiso de lectura.
     *
     * @return array<string, string>
     */
    private function readerHeaders(): array
    {
        // La factory de usuarios apunta al rol 3, que aquí no existe: este test
        // no hereda de `ApiTestCase` y nadie ha sembrado `user_roles`.
        DB::table('user_roles')->insertOrIgnore([
            'id' => 3, 'name' => 'user', 'display_name' => 'Usuario', 'slug' => 'usuario',
            'description' => 'Usuario normal', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $token = User::factory()->create()->createToken('test', [TokenAbilities::WEATHERSTATION_READ]);

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ];
    }
}
