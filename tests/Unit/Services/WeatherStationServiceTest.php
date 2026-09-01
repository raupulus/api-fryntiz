<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareType;
use App\Models\WeatherStation\Temperature;
use App\Services\WeatherStation\WeatherStationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeatherStationServiceTest extends TestCase
{
    use RefreshDatabase;

    private WeatherStationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(WeatherStationService::class);
    }

    private function makeStation(string $name, string $locationType = 'outdoor', string $zone = 'Azotea'): HardwareDevice
    {
        $type = HardwareType::firstOrCreate(['name' => HardwareType::WEATHER_STATION]);

        return HardwareDevice::create([
            'hardware_type_id' => $type->id,
            'name' => $name,
            'location_type' => $locationType,
            'zone' => $zone,
        ]);
    }

    /**
     * El motivo de API-06: pedir una zona costaba doce consultas POR ESTACIÓN.
     *
     * Este test no comprueba un número concreto de consultas —eso se rompería
     * al añadir un sensor sin que nada esté mal—, sino que el número NO CRECE
     * al añadir estaciones. Es la propiedad que importa y la que se perdería en
     * un refactor descuidado.
     */
    #[Test]
    public function el_coste_en_consultas_no_crece_con_el_numero_de_estaciones(): void
    {
        $dos = collect([$this->makeStation('A'), $this->makeStation('B')]);

        foreach ($dos as $station) {
            Temperature::create(['hardware_device_id' => $station->id, 'value' => 20.0, 'created_at' => now()]);
        }

        $consultasConDos = $this->contarConsultas(fn () => $this->service->getStationsReadings($dos));

        $cinco = $dos->concat([
            $this->makeStation('C'),
            $this->makeStation('D'),
            $this->makeStation('E'),
        ]);

        foreach ($cinco as $station) {
            Temperature::firstOrCreate(
                ['hardware_device_id' => $station->id],
                ['value' => 20.0, 'created_at' => now()]
            );
        }

        $consultasConCinco = $this->contarConsultas(fn () => $this->service->getStationsReadings($cinco));

        $this->assertSame(
            $consultasConDos,
            $consultasConCinco,
            'El número de consultas debe ser el mismo con dos estaciones que con cinco.'
        );
    }

    /**
     * La vía de varias estaciones tiene que devolver exactamente lo mismo que
     * la de una: comparten `buildReadings()` justamente para eso.
     */
    #[Test]
    public function la_lectura_por_lotes_coincide_con_la_individual(): void
    {
        $station = $this->makeStation('A');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 21.5, 'created_at' => now()]);

        $individual = $this->service->getStationReadings($station);
        $porLotes = $this->service->getStationsReadings(collect([$station]))[0];

        $this->assertSame($individual['temperature'], $porLotes['temperature']);
        $this->assertSame(array_keys($individual), array_keys($porLotes));
        $this->assertEquals($individual['wind'], $porLotes['wind']);
        $this->assertEquals($individual['air_quality'], $porLotes['air_quality']);
    }

    #[Test]
    public function sin_estaciones_devuelve_una_lista_vacia(): void
    {
        $this->assertSame([], $this->service->getStationsReadings(collect()));
    }

    #[Test]
    public function resolve_station_devuelve_la_primera_de_exterior(): void
    {
        $this->makeStation('Interior', 'indoor', 'Salón');
        $exterior = $this->makeStation('Exterior', 'outdoor', 'Azotea');

        $this->assertSame($exterior->id, $this->service->resolveStation()?->id);
    }

    #[Test]
    public function resolve_station_con_id_devuelve_esa_estacion(): void
    {
        $this->makeStation('Otra');
        $buscada = $this->makeStation('Buscada');

        $this->assertSame($buscada->id, $this->service->resolveStation($buscada->id)?->id);
    }

    private function contarConsultas(callable $accion): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $accion();

        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    }
}
