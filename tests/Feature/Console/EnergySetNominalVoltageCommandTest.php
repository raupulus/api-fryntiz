<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Hardware\EnergySystem;
use App\Models\Hardware\HardwareEnergy;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    private ?User $duenyo = null;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function elemento(string $nombre, bool $genera, ?EnergySystem $sistema = null): HardwareEnergy
    {
        return HardwareEnergy::create([
            'name' => $nombre,
            'is_generator' => $genera,
            'role' => $genera ? 'generator' : 'load',
            'energy_system_id' => $sistema?->id,
        ]);
    }

    private function instalacion(string $nombre, ?float $panel, float $bateria = 12.0): EnergySystem
    {
        return EnergySystem::create([
            'user_id' => $this->duenyo()->id,
            'name' => $nombre,
            'slug' => Str::slug($nombre),
            'nominal_voltage' => $bateria,
            'pv_nominal_voltage' => $panel,
        ]);
    }

    /**
     * `energy_systems.user_id` no admite nulos, así que hace falta un dueño.
     *
     * De instancia y no `static`: con `static` el usuario sobreviviría al
     * rollback de `RefreshDatabase` y el segundo test apuntaría a una fila que
     * ya no existe.
     */
    private function duenyo(): User
    {
        return $this->duenyo ??= User::factory()->create(['role_id' => 1]);
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

    /**
     * Lo que motivó que este dato viva en la base de datos y no en un flag: las
     * dos instalaciones reales tienen paneles a tensiones distintas.
     *
     *  - Renogy Rover: panel de 24 V (medido hasta 43,9), batería de 12.
     *  - Sunix 20A: panel de 12 V (llega a 18-20), batería de 12.
     *
     * Con un valor global, uno de los dos saldría al doble o a la mitad.
     */
    #[Test]
    public function cada_instalacion_usa_la_tension_de_su_propio_campo_solar(): void
    {
        $renogy = $this->instalacion('Renogy Rover 20 LI', panel: 24.0);
        $sunix = $this->instalacion('Sunix 20A', panel: 12.0);

        $panelRenogy = $this->elemento('Renogy · generación', genera: true, sistema: $renogy);
        $panelSunix = $this->elemento('Sunix · generación', genera: true, sistema: $sunix);
        $bateriaSunix = $this->elemento('Sunix · consumo', genera: false, sistema: $sunix);

        $this->artisan('energy:set-nominal-voltage --write')->assertSuccessful();

        $this->assertSame(24.0, (float) $panelRenogy->refresh()->nominal_voltage);
        $this->assertSame(12.0, (float) $panelSunix->refresh()->nominal_voltage);

        // La batería es de 12 en las dos, así que el consumo no cambia.
        $this->assertSame(12.0, (float) $bateriaSunix->refresh()->nominal_voltage);

        // Y el rango de cada panel admite lo que de verdad mide cada uno.
        $this->assertTrue($panelSunix->refresh()->voltageIsPlausible(20.0), 'El Sunix llega a 18-20 V.');
        $this->assertTrue($panelRenogy->refresh()->voltageIsPlausible(43.9), 'El Renogy llegó a 43,9 V.');

        // Pero el Sunix no puede admitir la tensión del Renogy: si un día
        // reporta 40 V es que pasa algo, no que el panel sea otro.
        $this->assertFalse($panelSunix->refresh()->voltageIsPlausible(43.9));
    }

    #[Test]
    public function la_tension_del_campo_solar_se_puede_declarar_al_vuelo(): void
    {
        $sunix = $this->instalacion('Sunix 20A', panel: null);
        $panel = $this->elemento('Sunix · generación', genera: true, sistema: $sunix);

        $this->artisan("energy:set-nominal-voltage --write --pv={$sunix->id}:12")
            ->assertSuccessful();

        // Queda guardada en la instalación, que es donde vive el dato.
        $this->assertSame(12.0, (float) $sunix->refresh()->pv_nominal_voltage);
        $this->assertSame(12.0, (float) $panel->refresh()->nominal_voltage);
    }

    #[Test]
    public function en_seco_la_tension_declarada_se_ve_pero_no_se_guarda(): void
    {
        $sunix = $this->instalacion('Sunix 20A', panel: null);
        $panel = $this->elemento('Sunix · generación', genera: true, sistema: $sunix);

        $this->artisan("energy:set-nominal-voltage --pv={$sunix->id}:12")
            ->expectsOutputToContain('Modo seco')
            ->assertSuccessful();

        $this->assertNull($sunix->refresh()->pv_nominal_voltage);
        $this->assertNull($panel->refresh()->nominal_voltage);
    }

    #[Test]
    public function una_declaracion_mal_escrita_no_deja_el_trabajo_a_medias(): void
    {
        $elemento = $this->elemento('Panel', genera: true);

        $this->artisan('energy:set-nominal-voltage --write --pv=veinticuatro')
            ->expectsOutputToContain('no vale')
            ->assertFailed();

        $this->assertNull($elemento->refresh()->nominal_voltage);
    }

    #[Test]
    public function una_instalacion_que_no_existe_tampoco(): void
    {
        $elemento = $this->elemento('Panel', genera: true);

        $this->artisan('energy:set-nominal-voltage --write --pv=9999:24')
            ->expectsOutputToContain('No hay ninguna instalación')
            ->assertFailed();

        $this->assertNull($elemento->refresh()->nominal_voltage);
    }
}
