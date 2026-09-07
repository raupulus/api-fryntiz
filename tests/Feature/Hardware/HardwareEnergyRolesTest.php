<?php

declare(strict_types=1);

namespace Tests\Feature\Hardware;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los papeles de un elemento de energía.
 *
 * Una fila de `hardware_energy` es **un papel de un dispositivo**, no el
 * dispositivo: el mismo aparato puede tener lo que produce (`generator`), lo que
 * gasta (`load`) y lo que almacena (`battery`).
 *
 * Hasta el 2026-09-07 había dos columnas diciendo lo mismo —`role` y
 * `is_generator`—, y la booleana no sabía expresar «batería»: la dejaba en
 * `false`, indistinguible de una carga. Y nada impedía crear cuatro generadores
 * del mismo aparato.
 */
class HardwareEnergyRolesTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monitor = HardwareDevice::create(['name' => 'Raspberry Pi Pico W']);
    }

    private function elemento(string $role, int $canal = 0, ?HardwareDevice $medido = null): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => ($medido ?? $this->monitor)->id,
            'role' => $role,
            'sensor_position' => $canal,
        ]);
    }

    // ── La tabla ─────────────────────────────────────────────────────────────

    #[Test]
    public function las_columnas_duplicadas_ya_no_existen(): void
    {
        $this->assertFalse(
            Schema::hasColumn('hardware_energy', 'is_generator'),
            '`is_generator` duplicaba a `role` y no sabía decir «batería».',
        );

        $this->assertFalse(
            Schema::hasColumn('hardware_energy', 'name'),
            'El nombre se compone del aparato y su papel: `display_name`.',
        );
    }

    #[Test]
    public function el_tercer_papel_se_llama_battery(): void
    {
        $this->assertSame('battery', HardwareEnergy::ROLE_BATTERY);

        $this->assertSame(
            ['generator', 'load', 'battery'],
            HardwareEnergy::ROLES,
        );

        $bateria = $this->elemento(HardwareEnergy::ROLE_BATTERY);

        $this->assertSame('battery', $bateria->refresh()->role);
    }

    #[Test]
    public function un_dispositivo_puede_tener_los_tres_papeles(): void
    {
        $this->elemento(HardwareEnergy::ROLE_GENERATOR);
        $this->elemento(HardwareEnergy::ROLE_LOAD);
        $this->elemento(HardwareEnergy::ROLE_BATTERY);

        $this->assertSame(3, HardwareEnergy::where('hardware_device_id', $this->monitor->id)->count());
    }

    // ── La restricción ───────────────────────────────────────────────────────

    #[Test]
    public function no_se_puede_repetir_el_mismo_papel_en_el_mismo_canal(): void
    {
        $this->elemento(HardwareEnergy::ROLE_GENERATOR, canal: 0);

        $this->expectException(QueryException::class);

        $this->elemento(HardwareEnergy::ROLE_GENERATOR, canal: 0);
    }

    /**
     * El caso real: una Raspberry con un INA que mide tres cosas distintas, cada
     * una por su canal y cada una midiendo **otro** aparato.
     */
    #[Test]
    public function un_monitor_puede_medir_varias_cargas_por_canales_distintos(): void
    {
        $ventilador = HardwareDevice::create(['name' => 'Ventilador']);
        $lampara = HardwareDevice::create(['name' => 'Lámpara']);
        $micro = HardwareDevice::create(['name' => 'Microcontrolador']);

        $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 1, medido: $ventilador);
        $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 2, medido: $lampara);
        $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 3, medido: $micro);

        $this->assertSame(3, HardwareEnergy::where('hardware_device_id', $this->monitor->id)->count());
    }

    /**
     * En PostgreSQL dos `NULL` no chocan entre sí en un índice único, así que
     * con el canal nullable la restricción no habría servido de nada. Por eso
     * la columna es `NOT NULL` con `0` por defecto.
     */
    #[Test]
    public function el_canal_no_admite_nulos(): void
    {
        $elemento = $this->elemento(HardwareEnergy::ROLE_LOAD);

        $this->assertSame(0, $elemento->refresh()->sensor_position);

        $this->expectException(QueryException::class);

        HardwareEnergy::create([
            'hardware_device_id' => $this->monitor->id,
            'hardware_device_monitorized_id' => $this->monitor->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => null,
        ]);
    }

    // ── Cuántos admite cada papel ────────────────────────────────────────────

    #[Test]
    public function generador_y_bateria_estan_limitados_a_uno_y_consumo_no(): void
    {
        $this->assertSame(1, HardwareEnergy::LIMITE_POR_ROL[HardwareEnergy::ROLE_GENERATOR]);
        $this->assertSame(1, HardwareEnergy::LIMITE_POR_ROL[HardwareEnergy::ROLE_BATTERY]);
        $this->assertNull(HardwareEnergy::LIMITE_POR_ROL[HardwareEnergy::ROLE_LOAD]);
    }

    // ── El nombre compuesto ──────────────────────────────────────────────────

    #[Test]
    public function el_nombre_sale_del_aparato_medido_y_su_papel(): void
    {
        $panel = HardwareDevice::create(['name' => 'Renogy Rover']);

        $elemento = $this->elemento(HardwareEnergy::ROLE_GENERATOR, medido: $panel);

        $this->assertSame('Renogy Rover · generador', $elemento->load('monitorized')->display_name);
    }

    /**
     * `display_name` se pinta en listados y en el aviso de cada lectura: si
     * tirara de la relación sería una consulta por fila. Con el lazy loading
     * desactivado, además, reventaría en vez de ir despacio en silencio.
     */
    #[Test]
    public function el_nombre_no_carga_relaciones_por_su_cuenta(): void
    {
        $panel = HardwareDevice::create(['name' => 'Renogy Rover']);
        $this->elemento(HardwareEnergy::ROLE_GENERATOR, medido: $panel);

        // Recién traído de la base de datos, sin relaciones cargadas.
        $sinCargar = HardwareEnergy::query()->sole();

        $this->assertStringContainsString('Elemento #', $sinCargar->display_name);
        $this->assertStringContainsString('generador', $sinCargar->display_name);

        // Y con la relación cargada, el nombre de verdad.
        $this->assertStringContainsString(
            'Renogy Rover',
            $sinCargar->load('monitorized')->display_name,
        );
    }

    #[Test]
    public function el_nombre_lleva_el_canal_solo_cuando_hay_varios(): void
    {
        $ventilador = HardwareDevice::create(['name' => 'Ventilador']);

        $sinCanal = $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 0, medido: $ventilador);
        $conCanal = $this->elemento(HardwareEnergy::ROLE_BATTERY, canal: 2, medido: $ventilador);

        $this->assertSame('Ventilador · consumo', $sinCanal->load('monitorized')->display_name);
        $this->assertSame('Ventilador · batería · canal 2', $conCanal->load('monitorized')->display_name);
    }

    // ── Las relaciones del dispositivo ───────────────────────────────────────

    #[Test]
    public function las_relaciones_del_dispositivo_filtran_por_papel(): void
    {
        $panel = HardwareDevice::create(['name' => 'Panel']);
        $router = HardwareDevice::create(['name' => 'Router']);
        $bateria = HardwareDevice::create(['name' => 'Banco de baterías']);

        $this->elemento(HardwareEnergy::ROLE_GENERATOR, canal: 1, medido: $panel);
        $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 2, medido: $router);
        $this->elemento(HardwareEnergy::ROLE_BATTERY, canal: 3, medido: $bateria);

        $generadores = $this->monitor->hardwareEnergyGenerator()->pluck('hardware_devices.id');
        $cargas = $this->monitor->hardwareEnergyLoad()->pluck('hardware_devices.id');

        $this->assertSame([$panel->id], $generadores->all());

        // Antes era «todo lo que no sea generador», así que la batería contaba
        // como carga. Ahora no.
        $this->assertSame([$router->id], $cargas->all());
    }
}
