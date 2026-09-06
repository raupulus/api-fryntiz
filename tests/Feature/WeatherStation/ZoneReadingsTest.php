<?php

declare(strict_types=1);

namespace Tests\Feature\WeatherStation;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\User;
use App\Models\WeatherStation\Humidity;
use App\Models\WeatherStation\Lightning;
use App\Models\WeatherStation\Pressure;
use App\Models\WeatherStation\Temperature;
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
    public function la_zona_toma_el_dato_mas_reciente_aunque_sea_de_otra_estacion(): void
    {
        $vieja = $this->makeStation('outdoor', 'Azotea', 'Vieja');
        $nueva = $this->makeStation('outdoor', 'Azotea', 'Nueva');

        // La que se quedó muda hace días, con el valor que se veía congelado.
        Humidity::create([
            'hardware_device_id' => $vieja->id,
            'value' => 49.0,
            'created_at' => now()->subDays(3),
        ]);

        // La que está subiendo ahora.
        Humidity::create([
            'hardware_device_id' => $nueva->id,
            'value' => 20.0,
            'created_at' => now(),
        ]);

        $lecturas = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertNotNull($lecturas);
        $this->assertSame(
            20.0,
            (float) $lecturas['humidity'],
            'La zona debe dar el dato fresco, no el de la estación que dejó de subir.'
        );
    }

    #[Test]
    public function cada_magnitud_va_por_su_cuenta(): void
    {
        $a = $this->makeStation('outdoor', 'Azotea', 'A');
        $b = $this->makeStation('outdoor', 'Azotea', 'B');

        // Una tiene la temperatura más reciente; la otra, la humedad.
        Temperature::create(['hardware_device_id' => $a->id, 'value' => 30.0, 'created_at' => now()]);
        Temperature::create(['hardware_device_id' => $b->id, 'value' => 10.0, 'created_at' => now()->subHour()]);
        Humidity::create(['hardware_device_id' => $a->id, 'value' => 90.0, 'created_at' => now()->subHour()]);
        Humidity::create(['hardware_device_id' => $b->id, 'value' => 40.0, 'created_at' => now()]);

        $lecturas = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(30.0, (float) $lecturas['temperature']);
        $this->assertSame(40.0, (float) $lecturas['humidity']);
    }

    #[Test]
    public function la_presion_vale_tambien_de_una_estacion_de_interior(): void
    {
        // El barómetro mide igual dentro que fuera y a la interperie se
        // estropea antes, así que suele vivir en un cacharro de interior.
        $fuera = $this->makeStation('outdoor', 'Azotea', 'Fuera');
        $dentro = $this->makeStation('indoor', 'Azotea', 'Dentro');

        Temperature::create(['hardware_device_id' => $fuera->id, 'value' => 25.0, 'created_at' => now()]);
        Pressure::create(['hardware_device_id' => $dentro->id, 'value' => 1013.0, 'created_at' => now()]);

        $lecturas = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(
            1013.0,
            (float) $lecturas['pressure'],
            'La presión es la excepción: vale cualquier estación de la zona.'
        );
    }

    #[Test]
    public function el_resto_de_sensores_no_se_cuela_desde_el_interior(): void
    {
        $fuera = $this->makeStation('outdoor', 'Azotea', 'Fuera');
        $dentro = $this->makeStation('indoor', 'Azotea', 'Dentro');

        Temperature::create(['hardware_device_id' => $fuera->id, 'value' => 25.0, 'created_at' => now()->subHour()]);
        // Más reciente, pero de interior: 22 grados dentro no son los de la calle.
        Temperature::create(['hardware_device_id' => $dentro->id, 'value' => 22.0, 'created_at' => now()]);

        $lecturas = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(25.0, (float) $lecturas['temperature']);
    }

    #[Test]
    public function los_rayos_se_cuentan_en_toda_la_zona(): void
    {
        $a = $this->makeStation('outdoor', 'Azotea', 'A');
        $b = $this->makeStation('outdoor', 'Azotea', 'B');

        Lightning::create(['hardware_device_id' => $a->id, 'distance' => 5, 'energy' => 100, 'created_at' => now()->subMinutes(5)]);
        Lightning::create(['hardware_device_id' => $b->id, 'distance' => 8, 'energy' => 120, 'created_at' => now()->subMinutes(10)]);

        $lecturas = $this->service()->getZoneReadings('Azotea', 'outdoor');

        $this->assertSame(2, $lecturas['lightning']['count_in_window']);
    }

    #[Test]
    public function una_zona_sin_estaciones_devuelve_null(): void
    {
        $this->assertNull($this->service()->getZoneReadings('Inexistente'));
    }

    #[Test]
    public function la_zona_principal_es_la_primera_de_exterior(): void
    {
        $this->makeStation('indoor', 'Salón', 'Interior');
        $this->makeStation('outdoor', 'Azotea', 'Exterior');

        $this->assertSame('Azotea', $this->service()->resolveMainZone());
    }

    #[Test]
    public function el_endpoint_de_zona_responde_con_el_dato_fresco(): void
    {
        $this->zonaConDosEstaciones();

        $this->getJson(
            route('api.v2.weather_stations.zone', ['zone' => 'Azotea', 'locationType' => 'outdoor']),
            $this->lectura()
        )
            ->assertOk()
            ->assertJsonPath('data.humidity', 20);
    }

    #[Test]
    public function el_endpoint_de_una_zona_vacia_responde_404(): void
    {
        $this->getJson(
            route('api.v2.weather_stations.zone', ['zone' => 'Inexistente']),
            $this->lectura()
        )->assertNotFound();
    }

    #[Test]
    public function el_endpoint_de_la_api_exige_permiso_de_lectura(): void
    {
        $this->zonaConDosEstaciones();

        $this->getJson(route('api.v2.weather_stations.zone', ['zone' => 'Azotea']))
            ->assertUnauthorized();
    }

    /**
     * El widget de la web se sirve desde el bloque web: sin token, porque no es
     * una integración sino una página propia, y con el mismo dato fresco.
     */
    #[Test]
    public function el_widget_web_da_el_dato_fresco_sin_token(): void
    {
        $this->zonaConDosEstaciones();

        $this->getJson(route('weather_station.widget.zone', ['zone' => 'Azotea', 'locationType' => 'outdoor']))
            ->assertOk()
            ->assertJsonPath('data.humidity', 20);
    }

    #[Test]
    public function el_widget_web_de_una_zona_vacia_responde_404(): void
    {
        $this->getJson(route('weather_station.widget.zone', ['zone' => 'Inexistente']))
            ->assertNotFound();
    }

    /**
     * Dos estaciones en la misma azotea: una con el dato viejo y otra con el
     * bueno.
     */
    private function zonaConDosEstaciones(): void
    {
        $vieja = $this->makeStation('outdoor', 'Azotea', 'Vieja');
        $nueva = $this->makeStation('outdoor', 'Azotea', 'Nueva');

        Humidity::create(['hardware_device_id' => $vieja->id, 'value' => 49.0, 'created_at' => now()->subDays(3)]);
        Humidity::create(['hardware_device_id' => $nueva->id, 'value' => 20.0, 'created_at' => now()]);
    }

    /**
     * Cabeceras de un cliente de la API con permiso de lectura.
     *
     * @return array<string, string>
     */
    private function lectura(): array
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
