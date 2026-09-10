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
    public function the_query_cost_does_not_grow_with_the_number_of_stations(): void
    {
        $twoStations = collect([$this->makeStation('A'), $this->makeStation('B')]);

        foreach ($twoStations as $station) {
            Temperature::create(['hardware_device_id' => $station->id, 'value' => 20.0, 'created_at' => now()]);
        }

        $queriesWithTwo = $this->countQueries(fn () => $this->service->getStationsReadings($twoStations));

        $fiveStations = $twoStations->concat([
            $this->makeStation('C'),
            $this->makeStation('D'),
            $this->makeStation('E'),
        ]);

        foreach ($fiveStations as $station) {
            Temperature::firstOrCreate(
                ['hardware_device_id' => $station->id],
                ['value' => 20.0, 'created_at' => now()]
            );
        }

        $queriesWithFive = $this->countQueries(fn () => $this->service->getStationsReadings($fiveStations));

        $this->assertSame(
            $queriesWithTwo,
            $queriesWithFive,
            'El número de consultas debe ser el mismo con dos estaciones que con cinco.'
        );
    }

    /**
     * La vía de varias estaciones tiene que devolver exactamente lo mismo que
     * la de una: comparten `buildReadings()` justamente para eso.
     */
    #[Test]
    public function the_batch_reading_matches_the_individual_one(): void
    {
        $station = $this->makeStation('A');
        Temperature::create(['hardware_device_id' => $station->id, 'value' => 21.5, 'created_at' => now()]);

        $individual = $this->service->getStationReadings($station);
        $batched = $this->service->getStationsReadings(collect([$station]))[0];

        $this->assertSame($individual['temperature'], $batched['temperature']);
        $this->assertSame(array_keys($individual), array_keys($batched));
        $this->assertEquals($individual['wind'], $batched['wind']);
        $this->assertEquals($individual['air_quality'], $batched['air_quality']);
    }

    #[Test]
    public function without_stations_it_returns_an_empty_list(): void
    {
        $this->assertSame([], $this->service->getStationsReadings(collect()));
    }

    #[Test]
    public function resolve_station_returns_the_first_outdoor_one(): void
    {
        $this->makeStation('Interior', 'indoor', 'Salón');
        $exterior = $this->makeStation('Exterior', 'outdoor', 'Azotea');

        $this->assertSame($exterior->id, $this->service->resolveStation()?->id);
    }

    #[Test]
    public function resolve_station_with_an_id_returns_that_station(): void
    {
        $this->makeStation('Otra');
        $target = $this->makeStation('Buscada');

        $this->assertSame($target->id, $this->service->resolveStation($target->id)?->id);
    }

    private function countQueries(callable $action): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $action();

        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    }
}
