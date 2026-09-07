<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Hardware\HardwareEnergy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `energy:set-nominal-voltage` rellena la tensión de respaldo de cada elemento.
 *
 * Lo que se prueba aquí es que **cada lado recibe la suya**: un controlador
 * solar tiene un elemento de generación que mide el panel (24 V) y otro de
 * consumo que mide la batería (12 V). Ponerles lo mismo sería repetir el error
 * que se acaba de quitar de `/hardware/energy`.
 */
class EnergySetNominalVoltageCommandTest extends TestCase
{
    use RefreshDatabase;

    private function elemento(string $nombre, bool $genera): HardwareEnergy
    {
        return HardwareEnergy::create([
            'name' => $nombre,
            'is_generator' => $genera,
            'role' => $genera ? 'generator' : 'load',
        ]);
    }

    #[Test]
    public function en_seco_no_escribe_nada(): void
    {
        $generador = $this->elemento('Panel', genera: true);

        $this->artisan('energy:set-nominal-voltage')
            ->expectsOutputToContain('Modo seco')
            ->assertSuccessful();

        $this->assertNull($generador->refresh()->nominal_voltage);
    }

    #[Test]
    public function el_generador_va_a_la_tension_del_panel(): void
    {
        $generador = $this->elemento('Renogy · generación', genera: true);

        $this->artisan('energy:set-nominal-voltage --write')->assertSuccessful();

        $generador->refresh();

        $this->assertSame(24.0, (float) $generador->nominal_voltage);

        // El mínimo, casi cero: de noche el panel no da nada y esa lectura es
        // correcta. Con el margen automático (24 × 0,5 = 12 V) se descartaría
        // el 44,7 % de las lecturas reales y se sustituirían por la nominal.
        $this->assertSame(0.1, (float) $generador->voltage_min);
        $this->assertGreaterThan(40.0, (float) $generador->voltage_max);
    }

    #[Test]
    public function la_carga_va_a_la_tension_de_la_bateria(): void
    {
        $carga = $this->elemento('Renogy · consumo', genera: false);

        $this->artisan('energy:set-nominal-voltage --write')->assertSuccessful();

        $carga->refresh();

        $this->assertSame(12.0, (float) $carga->nominal_voltage);
        $this->assertSame(10.0, (float) $carga->voltage_min);
        $this->assertSame(15.5, (float) $carga->voltage_max);
    }

    /**
     * El rango tiene que dar cabida a las lecturas reales del Renogy: el
     * generador llegó a 43,9 V y la carga se mueve entre 11,0 y 14,3 V.
     */
    #[Test]
    public function el_rango_admite_las_lecturas_reales_medidas(): void
    {
        $generador = $this->elemento('Panel', genera: true);
        $carga = $this->elemento('Batería', genera: false);

        $this->artisan('energy:set-nominal-voltage --write')->assertSuccessful();

        foreach ([0.4, 18.19, 33.7, 43.9] as $medida) {
            $this->assertTrue(
                $generador->refresh()->voltageIsPlausible($medida),
                "El generador debería admitir {$medida} V, que se ha medido de verdad.",
            );
        }

        foreach ([11.0, 12.69, 14.3] as $medida) {
            $this->assertTrue(
                $carga->refresh()->voltageIsPlausible($medida),
                "La carga debería admitir {$medida} V.",
            );
        }

        // Y lo que no es creíble sigue sin serlo.
        $this->assertFalse($carga->refresh()->voltageIsPlausible(33.7));
        $this->assertFalse($generador->refresh()->voltageIsPlausible(120.0));
    }

    #[Test]
    public function las_tensiones_se_pueden_cambiar_desde_la_linea_de_ordenes(): void
    {
        $generador = $this->elemento('Panel de 48', genera: true);
        $carga = $this->elemento('Banco de 24', genera: false);

        $this->artisan('energy:set-nominal-voltage --write --panel=48 --battery=24')
            ->assertSuccessful();

        $this->assertSame(48.0, (float) $generador->refresh()->nominal_voltage);
        $this->assertSame(24.0, (float) $carga->refresh()->nominal_voltage);
    }

    #[Test]
    public function ejecutarlo_dos_veces_no_cambia_nada_la_segunda(): void
    {
        $this->elemento('Panel', genera: true);

        $this->artisan('energy:set-nominal-voltage --write')->assertSuccessful();

        $this->artisan('energy:set-nominal-voltage --write')
            ->expectsOutputToContain('Todo estaba ya en su sitio')
            ->assertSuccessful();
    }
}
