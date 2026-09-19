<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\Hardware\HardwareType;
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

    /**
     * Un aparato de la instalación solar.
     *
     * El tipo importa: los totales de la cabecera son los del sistema
     * fotovoltaico y sólo suman los aparatos de este tipo.
     */
    private function device(string $name): HardwareDevice
    {
        return HardwareDevice::create([
            'name' => $name,
            'hardware_type_id' => HardwareType::firstOrCreate(['slug' => HardwareType::SOLAR_CONTROLLER_SLUG], ['name' => 'Controlador Solar'])->id,
        ]);
    }

    /**
     * Un aparato enchufado a la red de casa, que mide su propio consumo.
     */
    private function mainsDevice(string $name): HardwareDevice
    {
        return HardwareDevice::create([
            'name' => $name,
            'hardware_type_id' => HardwareType::firstOrCreate(['slug' => 'micro-pc'], ['name' => 'Micro PC'])->id,
        ]);
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

        $generator = $response->viewData('generator');
        $load = $response->viewData('load');

        // El balance en vatios es la pregunta de verdad y no depende de la
        // tensión: 64 - 28 = 36 W a favor.
        $this->assertSame(36.0, round($generator->current - $load->current));

        // Y las dos tensiones, para que se vea que no son la misma.
        $this->assertSame('33.7', $generator->current_voltage);
        $this->assertSame('13.2', $load->current_voltage);

        // **Los amperios que se pintan van referidos a la tensión del bus.**
        // Sin elemento de batería configurado, la referencia es la nominal del
        // consumo y, a falta de ella, los 12 V de la instalación real. Los
        // 1,85 A medidos a 33,7 V son 64 W, que a 12 V son 5,3 A: sumar el
        // 1,85 crudo contra los 2,16 del consumo daría un balance al revés.
        $this->assertSame(5.3, round((float) $generator->current_amperage, 1));
        $this->assertSame('2.3', $load->current_amperage);

        // Y hay una tarjeta de balance en amperios; sin batería configurada va
        // a la tensión de referencia: 36 W / 12 V = 3 A.
        $balanceAmps = collect($response->viewData('currentStats'))
            ->first(fn (array $stat): bool => $stat['title'] === 'Balance' && $stat['unit'] === 'A');
        $this->assertSame(3.0, (float) $balanceAmps['value']);
    }

    /**
     * Los Ah del día son los que cuenta el regulador, no los Wh entre 12: el
     * Rover los mide a la tensión real de la batería, que ronda los 13 V.
     */
    #[Test]
    public function todays_amp_hours_are_the_controllers_own(): void
    {
        $device = $this->device('Rover');
        $this->generatedAllTime($device, 1_000);

        foreach ([[$this->generatorEnergy($device), 617, 47], [$this->loadEnergy($device), 376, 30]] as [$element, $wh, $ah]) {
            HardwareEnergyToday::create([
                'hardware_device_id' => $device->id,
                'hardware_energy_id' => $element->id,
                'energy_wh' => $wh,
                'energy_ah' => $ah,
                'date' => today(),
            ]);
        }

        $response = $this->get(route('hardware.energy.index'))->assertOk();

        // 617 / 12 serían 51 y 376 / 12, 31.
        $this->assertSame(47.0, (float) $response->viewData('generator')->today_amperage);
        $this->assertSame(30.0, (float) $response->viewData('load')->today_amperage);
    }

    /**
     * El balance en amperios va a la tensión real de la batería: 39 W a 13,7 V
     * son 2,8 A, no los 3,3 que salen dividiendo entre 12.
     */
    #[Test]
    public function the_amp_balance_uses_the_measured_battery_voltage(): void
    {
        $device = $this->device('Rover');
        $this->generatedAllTime($device, 1_000);
        $this->generatingNow($device, 66);
        $this->consumingNow($device, 27);

        $battery = HardwareEnergy::create([
            'hardware_device_id' => $device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $battery->id,
            'voltage' => 13.7,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->get(route('hardware.energy.index'))->assertOk();

        $balanceAmps = collect($response->viewData('currentStats'))
            ->first(fn (array $stat): bool => $stat['title'] === 'Balance' && $stat['unit'] === 'A');

        $this->assertSame(2.8, (float) $balanceAmps['value']);
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

    /**
     * Una instalación tiene **una** batería, y su tensión y su carga son las
     * suyas, no las del consumo.
     *
     * El esquema viejo replicaba el porcentaje del banco en las filas de
     * generación y de consumo, y de ahí salían dos tarjetas —«Bat. Charge» y
     * «Bat. Load»— con el mismo número leído de sitios distintos. La de
     * tensiones, además, decía «Panel / Batería» y enseñaba la del **consumo**.
     */
    #[Test]
    public function there_is_one_battery_card_and_it_reads_the_battery(): void
    {
        $device = $this->device('Solar');
        $this->generatedAllTime($device, 1_000);

        // El panel a 24 V.
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $this->generatorEnergy($device)->id,
            'voltage' => 24.6, 'amperage' => 2.0, 'power' => 49,
            'created_at' => now()->subMinutes(5),
        ]);

        // El consumo a 12,2 V, con el porcentaje del banco replicado.
        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $this->loadEnergy($device)->id,
            'voltage' => 12.2, 'amperage' => 1.0, 'power' => 12,
            'battery_percentage' => 55,
            'created_at' => now()->subMinutes(5),
        ]);

        // Y la batería, que es quien manda: 13,4 V y 91 %.
        $bateria = HardwareEnergy::firstOrCreate([
            'hardware_device_id' => $device->id,
            'role' => HardwareEnergy::ROLE_BATTERY,
        ], ['sensor_position' => 0, 'is_active' => true]);

        HardwareEnergyReading::create([
            'hardware_device_id' => $device->id,
            'hardware_energy_id' => $bateria->id,
            'voltage' => 13.4, 'amperage' => 3.0, 'power' => 40,
            'battery_percentage' => 91,
            'created_at' => now()->subMinutes(5),
        ]);

        $titulos = collect($this->get(route('hardware.energy.index'))->assertOk()->viewData('currentStats'))
            ->pluck('title');

        $this->assertFalse($titulos->contains('Bat. Load'), 'Sobraba: es el mismo dato que la otra.');
        $this->assertFalse($titulos->contains('Bat. Charge'));
        $this->assertTrue($titulos->contains('Batería'));

        $tarjetas = collect($this->get(route('hardware.energy.index'))->viewData('currentStats'))
            ->keyBy('title');

        $this->assertSame('91', $tarjetas['Batería']['value'], 'El porcentaje sale del elemento batería.');

        // Y las tres tensiones, cada una la suya.
        $this->assertSame('24.6 / 13.4 / 12.2', $tarjetas['Panel / Bat. / Consumo']['value']);
    }

    /**
     * Lo de arriba es la instalación solar; lo de abajo, cada aparato.
     *
     * La Raspberry Pi 5 mide su propio consumo y el de su Hailo-8, pero está
     * enchufada a la red de casa. Sus vatios entraban en los totales de
     * cabecera como si fueran de la instalación, y su tensión se promediaba con
     * la del Rover: la tarjeta «Panel / Bat. / Consumo» acabó enseñando 7 V de
     * consumo, que es la media de 12,5, 5,1 y 3,3 y no la tensión de ningún
     * sitio.
     */
    #[Test]
    public function the_totals_at_the_top_only_count_the_solar_installation(): void
    {
        $solar = $this->device('Controlador solar');
        $this->consumingNow($solar, 12);
        $this->consumedToday($solar, 100);
        $this->consumedAllTime($solar, 1_000);

        $enchufada = $this->mainsDevice('Raspberry Pi 5');
        $this->consumedToday($enchufada, 50);
        $this->consumedAllTime($enchufada, 2_000);

        HardwareEnergyReading::create([
            'hardware_device_id' => $enchufada->id,
            'hardware_energy_id' => $this->loadEnergy($enchufada)->id,
            'voltage' => 5.0, 'amperage' => 1.0, 'power' => 5,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->get(route('hardware.energy.index'))->assertOk();
        $load = $response->viewData('load');

        $this->assertSame(12.0, (float) $load->current, 'Los 5 W de la red no son de la instalación.');
        $this->assertSame(100.0, (float) $load->today);
        $this->assertSame('1.0', $load->historical);

        $tarjetas = collect($response->viewData('currentStats'))->keyBy('title');
        $this->assertStringEndsWith('/ 12', $tarjetas['Panel / Bat. / Consumo']['value'], 'La tensión de consumo es la del bus solar, no una media con los 5 V.');

        // Y sigue teniendo su tarjeta abajo, que para eso mide.
        $this->assertContains($enchufada->id, $response->viewData('hardwareItems')->pluck('id')->all());
        $this->assertSame(50.0, $response->viewData('devicesStats')[$enchufada->id]->consumed_today);
    }
}
