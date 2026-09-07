<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwarePowerGenerator;
use App\Models\Hardware\HardwarePowerGeneratorHistorical;
use App\Models\Hardware\HardwarePowerGeneratorToday;
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

    private function device(string $nombre): HardwareDevice
    {
        return HardwareDevice::create(['name' => $nombre]);
    }

    /**
     * Una lectura de hace un rato, para que cuente como «ahora mismo».
     */
    private function generandoAhora(HardwareDevice $device, float $vatios): void
    {
        HardwarePowerGenerator::create([
            'hardware_device_id' => $device->id,
            'voltage' => 12.0,
            'amperage' => $vatios / 12.0,
            'power' => $vatios,
            'read_at' => now()->subMinutes(5),
        ]);
    }

    private function generadoHoy(HardwareDevice $device, float $wh): void
    {
        HardwarePowerGeneratorToday::create([
            'hardware_device_id' => $device->id,
            'energy_wh' => $wh,
            'date' => today(),
        ]);
    }

    private function generadoSiempre(HardwareDevice $device, float $wh): void
    {
        HardwarePowerGeneratorHistorical::create([
            'hardware_device_id' => $device->id,
            'energy_wh' => $wh,
            'days_operating' => 100,
        ]);
    }

    /**
     * @return list<int> Ids en el orden en que la página los va a pintar.
     */
    private function ordenDeLasTarjetas(): array
    {
        $respuesta = $this->get(route('hardware.energy.index'));
        $respuesta->assertOk();

        return $respuesta->viewData('hardwareItems')->pluck('id')->all();
    }

    #[Test]
    public function los_que_estan_dando_senales_ahora_van_primero(): void
    {
        // Sólo histórico: lleva tiempo parado.
        $parado = $this->device('Parado');
        $this->generadoSiempre($parado, 50_000);

        // Activo ahora mismo, pero con muy poco histórico.
        $activo = $this->device('Activo');
        $this->generandoAhora($activo, 120);
        $this->generadoHoy($activo, 300);
        $this->generadoSiempre($activo, 10);

        $this->assertSame([$activo->id, $parado->id], $this->ordenDeLasTarjetas());
    }

    #[Test]
    public function entre_los_activos_manda_lo_que_han_movido_hoy(): void
    {
        $poco = $this->device('Poco hoy');
        $this->generandoAhora($poco, 10);
        $this->generadoHoy($poco, 50);
        $this->generadoSiempre($poco, 90_000);

        $mucho = $this->device('Mucho hoy');
        $this->generandoAhora($mucho, 10);
        $this->generadoHoy($mucho, 900);
        $this->generadoSiempre($mucho, 10);

        $this->assertSame([$mucho->id, $poco->id], $this->ordenDeLasTarjetas());
    }

    #[Test]
    public function entre_los_parados_manda_el_acumulado_de_siempre(): void
    {
        $flojo = $this->device('Flojo');
        $this->generadoSiempre($flojo, 100);

        $veterano = $this->device('Veterano');
        $this->generadoSiempre($veterano, 900_000);

        $this->assertSame([$veterano->id, $flojo->id], $this->ordenDeLasTarjetas());
    }

    #[Test]
    public function el_orden_es_estable_entre_recargas(): void
    {
        foreach (['Uno', 'Dos', 'Tres', 'Cuatro'] as $nombre) {
            $this->generadoSiempre($this->device($nombre), random_int(1, 1000));
        }

        $this->assertSame($this->ordenDeLasTarjetas(), $this->ordenDeLasTarjetas());
    }

    /**
     * Ordenar no puede alterar ninguna suma de las tarjetas de cabecera.
     */
    #[Test]
    public function los_totales_de_arriba_no_cambian(): void
    {
        $a = $this->device('A');
        $this->generandoAhora($a, 100);
        $this->generadoHoy($a, 400);
        $this->generadoSiempre($a, 1_000);

        $b = $this->device('B');
        $this->generadoHoy($b, 600);
        $this->generadoSiempre($b, 2_000);

        $respuesta = $this->get(route('hardware.energy.index'));
        $respuesta->assertOk();

        $generator = $respuesta->viewData('generator');

        $this->assertSame(100.0, (float) $generator->current);
        $this->assertSame(1000.0, (float) $generator->today);
        $this->assertSame('3.0', $generator->historical);
    }
}
