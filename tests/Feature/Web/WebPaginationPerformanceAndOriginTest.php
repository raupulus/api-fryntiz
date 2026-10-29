<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Http\Middleware\EnsurePaginationIsSameOrigin;
use App\Models\Gdacs\GdacsEvent;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\WeatherStation\Temperature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Paginación eficiente (Deferred Join + conteo cacheado) y protección por
 * origen en las rutas web públicas.
 *
 * Verifica:
 *  1. Que la página 1 es accesible sin `Referer` (usuarios directos y Googlebot).
 *  2. Que las páginas `?page > 1` sin `Referer` o desde otro origen reciben 403
 *     sin ejecutar consultas a las tablas de sensores.
 *  3. Que con `Referer` del propio dominio se puede navegar a cualquier página
 *     profunda (incluida `page > 100`), resolviendo mediante Deferred Join,
 *     con conteo cacheado y cabecera `X-Robots-Tag: noindex, nofollow`.
 *  4. Que el `<link rel="canonical">` conserva `?station=X` y omite `?page=N`.
 *  5. Que `/keycounter` acota `month` y `year` dentro del rango válido.
 */
class WebPaginationPerformanceAndOriginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * Cabeceras simulando clic en paginación desde la propia web.
     *
     * @return array<string, string>
     */
    private function sameOriginHeaders(): array
    {
        return [
            'Referer' => config('app.url').'/weatherstation',
        ];
    }

    private function createStation(string $name = 'Estación Principal'): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'name' => $name,
            'location_type' => 'outdoor',
            'zone' => 'Azotea',
        ]);
    }

    #[Test]
    public function page_one_is_accessible_without_referer_for_direct_visitors_and_search_engines(): void
    {
        $station = $this->createStation();
        Temperature::create([
            'hardware_device_id' => $station->id,
            'value' => 22.5,
            'created_at' => now(),
        ]);

        $response = $this->get(route('weather_station.sensor', [
            'type' => 'temperature',
            'station' => $station->id,
        ]));

        $response->assertOk();
        $this->assertFalse($response->headers->has('X-Robots-Tag'));
    }

    #[Test]
    public function deep_pagination_without_same_origin_is_blocked_before_touching_the_database(): void
    {
        $station = $this->createStation();
        Temperature::create([
            'hardware_device_id' => $station->id,
            'value' => 22.5,
            'created_at' => now(),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('weather_station.sensor', [
            'type' => 'temperature',
            'page' => 139847,
        ]))->assertForbidden();

        $this->get(
            route('weather_station.sensor', ['type' => 'temperature', 'page' => 2]),
            ['Referer' => 'https://scraper-externo.example/bot']
        )->assertForbidden();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(
            0,
            $queries,
            'Una petición bloqueada por origen en ?page > 1 no debe ejecutar ninguna consulta SQL.'
        );
    }

    #[Test]
    public function any_page_beyond_100_is_reachable_from_same_origin_and_sets_noindex_header(): void
    {
        $station = $this->createStation();
        Temperature::create([
            'hardware_device_id' => $station->id,
            'value' => 24.1,
            'created_at' => now(),
        ]);

        // Simulamos un total alto en caché para navegar a la página 500 sin insertar 12.500 filas en el test.
        Cache::put('weather:sensor_count:temperature:'.$station->id, 3500000, 3600);

        $response = $this->get(
            route('weather_station.sensor', [
                'type' => 'temperature',
                'station' => $station->id,
                'page' => 500,
            ]),
            $this->sameOriginHeaders()
        );

        $response->assertOk();
        $response->assertHeader('X-Robots-Tag', EnsurePaginationIsSameOrigin::ROBOTS_NOINDEX);
    }

    #[Test]
    public function deferred_paginator_caches_total_count_and_fetches_exact_page_items(): void
    {
        $station = $this->createStation();

        for ($i = 1; $i <= 30; $i++) {
            Temperature::create([
                'hardware_device_id' => $station->id,
                'value' => 10.0 + $i,
                'created_at' => now()->subMinutes(31 - $i),
            ]);
        }

        // Página 1 (25 registros más recientes: del 30 al 6)
        $page1 = $this->get(route('weather_station.sensor', [
            'type' => 'temperature',
            'station' => $station->id,
        ]));
        $page1->assertOk();
        $this->assertSame(30, Cache::get('weather:sensor_count:temperature:'.$station->id));

        // Página 2 (5 registros más antiguos: del 5 al 1) con mismo origen
        $page2 = $this->get(
            route('weather_station.sensor', [
                'type' => 'temperature',
                'station' => $station->id,
                'page' => 2,
            ]),
            $this->sameOriginHeaders()
        );
        $page2->assertOk();
        $records = $page2->viewData('records');
        $this->assertCount(5, $records);
    }

    #[Test]
    public function sensor_canonical_url_preserves_station_parameter_and_strips_page(): void
    {
        $station = $this->createStation();
        Temperature::create([
            'hardware_device_id' => $station->id,
            'value' => 21.0,
            'created_at' => now(),
        ]);

        $response = $this->get(
            route('weather_station.sensor', [
                'type' => 'temperature',
                'station' => $station->id,
                'page' => 2,
            ]),
            $this->sameOriginHeaders()
        );

        $response->assertOk();
        $expectedCanonical = route('weather_station.sensor', [
            'type' => 'temperature',
            'station' => $station->id,
        ]);

        $response->assertSee('<link rel="canonical" href="'.$expectedCanonical.'"/>', false);
    }

    #[Test]
    public function gdacs_and_airflight_protect_deep_pages_by_origin_while_keeping_page_one_public(): void
    {
        GdacsEvent::create([
            'event_type' => 'EQ',
            'event_id' => 1001,
            'episode_id' => 1,
            'name' => 'Terremoto Golfo de Cádiz',
            'alert_level' => 'Green',
            'is_current' => true,
            'from_date' => now()->subDay(),
            'last_modified_at' => now(),
            'lat' => 36.7,
            'lon' => -6.4,
            'distance_km' => 15.0,
        ]);

        $this->get(route('weather_station.gdacs.index'))->assertOk();
        $this->get(route('weather_station.gdacs.index', ['page' => 2]))->assertForbidden();
        $this->get(route('weather_station.gdacs.index', ['page' => 2]), $this->sameOriginHeaders())->assertOk();

        $this->get(route('airflight.index'))->assertOk();
        $this->get(route('airflight.index', ['page' => 2]))->assertForbidden();
        $this->get(route('airflight.index', ['page' => 2]), $this->sameOriginHeaders())->assertOk();
    }

    #[Test]
    public function invalid_page_parameter_returns_not_found(): void
    {
        $this->get(route('weather_station.gdacs.index', ['page' => 0]))->assertNotFound();
        $this->get(route('weather_station.gdacs.index', ['page' => -5]))->assertNotFound();
        $this->get(route('weather_station.gdacs.index', ['page' => 'abc']))->assertNotFound();
    }

    #[Test]
    public function keycounter_rejects_out_of_range_years_and_months(): void
    {
        $this->get(route('keycounter.index', ['year' => 1800, 'month' => 1]))->assertNotFound();
        $this->get(route('keycounter.index', ['year' => 2099, 'month' => 1]))->assertNotFound();
        $this->get(route('keycounter.index', ['year' => 2025, 'month' => 13]))->assertNotFound();
        $this->get(route('keycounter.index', ['year' => 2025, 'month' => 0]))->assertNotFound();
    }
}
