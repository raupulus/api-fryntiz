<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * En `/hardware/energy`, lo que está funcionando va arriba.
 *
 * La consulta que trae los dispositivos no llevaba `ORDER BY`, así que las
 * tarjetas salían en el orden que le conviniera a PostgreSQL: ni siquiera era
 * estable entre recargas.
 */
class EnergyCardOrderTest extends TestCase
{
    use RefreshDatabase;

    private function device(string $name): HardwareDevice
    {
        return HardwareDevice::create(['name' => $name]);
    }

    private function generatorEnergy(HardwareDevice $device): HardwareEnergy
    {
        return HardwareEnergy::firstOrCreate([
            'hardware_device_id' => $device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
        ], [
            'sensor_position' => 0,
            'is_active' => true,
        ]);
    }

    private function loadEnergy(HardwareDevice $device): HardwareEnergy
    {
        return HardwareEnergy::firstOrCreate([
            'hardware_device_id' => $device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
        ], [
            'sensor_position' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * Una lectura de hace un rato, para que cuente como «ahora mismo».
     */
    private function generatingNow(HardwareDevice $device, float $watts): void
    {
        $energy = $this->generatorEnergy($device);

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $energy->id,
            'voltage' => 12.0,
            'amperage' => $watts / 12.0,
            'power' => $watts,
            'created_at' => now()->subMinutes(5),
        ]);
    }

    private function generatedToday(HardwareDevice $device, float $wh): void
    {
        $energy = $this->generatorEnergy($device);

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $energy->id,
            'energy_wh' => $wh,
            'energy_ah' => $wh / 12.0,
            'date' => today(),
        ]);
    }

    private function generatedAllTime(HardwareDevice $device, float $wh): void
    {
        $energy = $this->generatorEnergy($device);

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $energy->id,
            'energy_wh' => $wh,
            'energy_ah' => $wh / 12.0,
            'days_operating' => 100,
            'session_index' => 1,
        ]);
    }

    private function consumingNow(HardwareDevice $device, float $watts): void
    {
        $energy = $this->loadEnergy($device);

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $energy->id,
            'voltage' => 12.0,
            'amperage' => $watts / 12.0,
            'power' => $watts,
            'created_at' => now()->subMinutes(5),
        ]);
    }

    private function consumedToday(HardwareDevice $device, float $wh): void
    {
        $energy = $this->loadEnergy($device);

        HardwareEnergyToday::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $energy->id,
            'energy_wh' => $wh,
            'energy_ah' => $wh / 12.0,
            'date' => today(),
        ]);
    }

    private function consumedAllTime(HardwareDevice $device, float $wh): void
    {
        $energy = $this->loadEnergy($device);

        HardwareEnergyHistorical::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $energy->id,
            'energy_wh' => $wh,
            'energy_ah' => $wh / 12.0,
            'days_operating' => 100,
            'session_index' => 1,
        ]);
    }

    /**
     * @return list<int> Ids en el orden en que la página los va a pintar.
     */
    private function cardOrder(): array
    {
        $response = $this->get(route('hardware.energy.index'));
        $response->assertOk();

        return $response->viewData('hardwareItems')->pluck('id')->all();
    }

    #[Test]
    public function devices_currently_reporting_come_first(): void
    {
        // Sólo histórico: lleva tiempo parado.
        $stopped = $this->device('Parado');
        $this->generatedAllTime($stopped, 50_000);

        // Activo ahora mismo, pero con muy poco histórico.
        $active = $this->device('Activo');
        $this->generatingNow($active, 120);
        $this->generatedToday($active, 300);
        $this->generatedAllTime($active, 10);

        $this->assertSame([$active->id, $stopped->id], $this->cardOrder());
    }

    #[Test]
    public function among_active_devices_todays_output_wins(): void
    {
        $little = $this->device('Poco hoy');
        $this->generatingNow($little, 10);
        $this->generatedToday($little, 50);
        $this->generatedAllTime($little, 90_000);

        $lots = $this->device('Mucho hoy');
        $this->generatingNow($lots, 10);
        $this->generatedToday($lots, 900);
        $this->generatedAllTime($lots, 10);

        $this->assertSame([$lots->id, $little->id], $this->cardOrder());
    }

    #[Test]
    public function among_stopped_devices_the_all_time_total_wins(): void
    {
        $modest = $this->device('Flojo');
        $this->generatedAllTime($modest, 100);

        $veteran = $this->device('Veterano');
        $this->generatedAllTime($veteran, 900_000);

        $this->assertSame([$veteran->id, $modest->id], $this->cardOrder());
    }

    #[Test]
    public function the_order_is_stable_across_reloads(): void
    {
        foreach (['Uno', 'Dos', 'Tres', 'Cuatro'] as $name) {
            $this->generatedAllTime($this->device($name), random_int(1, 1000));
        }

        $this->assertSame($this->cardOrder(), $this->cardOrder());
    }

    /**
     * Generación y consumo se miden a tensiones distintas —el panel y la
     * batería—, así que sus amperios no se pueden enfrentar. La página lo
     * hacía: dos tarjetas «Generando X A» y «Consumiendo Y A» una al lado de
     * la otra. Con los datos reales del 5 de septiembre de 2026, eso daba
     * 1,85 A generando frente a 2,16 A consumiendo, o sea la impresión de que
     * se consume más de lo que se genera, cuando en vatios eran 64 W contra
     * 28 W: se genera más del doble.
     */
    #[Test]
    public function the_amperages_of_the_two_sides_are_not_pitted_against_each_other(): void
    {
        $device = $this->device('Solar');

        // La página sólo mira los dispositivos que tienen histórico.
        $this->generatedAllTime($device, 1_000);

        // Generando en el lado del panel: mucha tensión, poca corriente.
        $genEnergy = $this->generatorEnergy($device);
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $genEnergy->id,
            'voltage' => 33.7,
            'amperage' => 1.85,
            'power' => 64,
            'created_at' => now()->subMinutes(5),
        ]);

        // Consumiendo en el lado de la batería: poca tensión, más corriente.
        $loadEnergy = $this->loadEnergy($device);
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $loadEnergy->id,
            'voltage' => 13.2,
            'amperage' => 2.16,
            'power' => 28,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->get(route('hardware.energy.index'))->assertOk();

        $titles = collect($response->viewData('currentStats'))->pluck('title');
        $units = collect($response->viewData('currentStats'))
            ->filter(fn (array $s) => $s['unit'] === 'A');

        $this->assertTrue($units->isEmpty(), 'No debe quedar ninguna tarjeta en amperios.');
        $this->assertTrue($titles->contains('Balance'));

        $generator = $response->viewData('generator');
        $load = $response->viewData('load');

        // El balance, que es la pregunta de verdad: 64 - 28 = 36 W a favor.
        $this->assertSame(36.0, round($generator->current - $load->current));

        // Y las dos tensiones, para que se vea que no son la misma.
        $this->assertSame('33.7', $generator->current_voltage);
        $this->assertSame('13.2', $load->current_voltage);
    }

    /**
     * Ordenar no puede alterar ninguna suma de las tarjetas de cabecera.
     */
    #[Test]
    public function the_totals_at_the_top_do_not_change(): void
    {
        $a = $this->device('A');
        $this->generatingNow($a, 100);
        $this->generatedToday($a, 400);
        $this->generatedAllTime($a, 1_000);

        $b = $this->device('B');
        $this->generatedToday($b, 600);
        $this->generatedAllTime($b, 2_000);

        $response = $this->get(route('hardware.energy.index'));
        $response->assertOk();

        $generator = $response->viewData('generator');

        $this->assertSame(100.0, (float) $generator->current);
        $this->assertSame(1000.0, (float) $generator->today);
        $this->assertSame('3.0', $generator->historical);
    }

    #[Test]
    public function consuming_devices_currently_reporting_come_first(): void
    {
        $stopped = $this->device('Consumo Parado');
        $this->consumedAllTime($stopped, 50_000);

        $active = $this->device('Consumo Activo');
        $this->consumingNow($active, 80);
        $this->consumedToday($active, 200);
        $this->consumedAllTime($active, 10);

        $this->assertSame([$active->id, $stopped->id], $this->cardOrder());
    }

    #[Test]
    public function mixed_devices_order_by_combined_activity(): void
    {
        $genDevice = $this->device('Generador Fuerte');
        $this->generatingNow($genDevice, 50);
        $this->generatedToday($genDevice, 500);

        $loadDevice = $this->device('Carga Fuerte');
        $this->consumingNow($loadDevice, 50);
        $this->consumedToday($loadDevice, 800);

        // Ambos están activos (reporting now), pero la carga movió 800 Wh hoy vs 500 Wh del generador
        $this->assertSame([$loadDevice->id, $genDevice->id], $this->cardOrder());
    }
}
