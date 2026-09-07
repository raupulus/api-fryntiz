<?php

declare(strict_types=1);

namespace Tests\Feature\KeyCounter;

use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Services\KeyCounter\KeyCounterService;
use App\Support\KeyCounter\KeyCounterCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * La caché de las gráficas de KeyCounter.
 *
 * `getStatisticsPreparedToGraphics()` era lo único caro de la página que no
 * estaba cacheado: agrega todas las rachas del mes por día y por dispositivo.
 * Sobre el volcado real tarda más de un segundo.
 *
 * El fallo más fácil de cometer aquí —y el más difícil de ver, porque la página
 * sigue funcionando— es que la clave no distinga el mes, y navegar entre meses
 * enseñe los datos de otro. Eso tiene su propio test.
 */
class KeyCounterCacheTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->device = HardwareDevice::create(['name' => 'Thinkpad']);
    }

    private function racha(string $cuando, int $pulsaciones): void
    {
        $racha = Keyboard::create([
            'hardware_device_id' => $this->device->id,
            'start_at' => $cuando,
            'end_at' => $cuando,
            'duration' => 300,
            'pulsations' => $pulsaciones,
            'pulsations_special_keys' => 1,
            'pulsation_average' => 2.0,
            'score' => 10,
            'weekday' => 0,
        ]);

        // El resumen agrupa por `created_at`, que no está en `$fillable`.
        $racha->forceFill(['created_at' => $cuando])->save();
    }

    /**
     * @return array{int, mixed}
     */
    private function conConsultas(callable $accion): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $resultado = $accion();
        $consultas = count(DB::getQueryLog());

        DB::disableQueryLog();

        return [$consultas, $resultado];
    }

    #[Test]
    public function la_segunda_visita_a_un_mes_cerrado_no_consulta_nada(): void
    {
        $this->racha('2025-03-15 10:00:00', 500);

        [$primera] = $this->conConsultas(
            fn () => $this->get('/keycounter?month=3&year=2025')->assertOk()
        );

        [$segunda] = $this->conConsultas(
            fn () => $this->get('/keycounter?month=3&year=2025')->assertOk()
        );

        $this->assertGreaterThan(0, $primera);
        $this->assertLessThan(
            $primera,
            $segunda,
            'La segunda visita debería salir de la caché y consultar menos.'
        );
    }

    /**
     * El fallo que rompería la página de verdad: que un mes enseñe los datos de
     * otro porque la clave no los distingue.
     */
    #[Test]
    public function cada_mes_tiene_su_propia_entrada(): void
    {
        $this->racha('2025-03-15 10:00:00', 500);
        $this->racha('2025-04-15 10:00:00', 900);
        $this->racha('2024-03-15 10:00:00', 111);

        $marzo25 = $this->get('/keycounter?month=3&year=2025')->assertOk();
        $abril25 = $this->get('/keycounter?month=4&year=2025')->assertOk();
        $marzo24 = $this->get('/keycounter?month=3&year=2024')->assertOk();

        $total = fn ($r) => $r->viewData('keyboard_statistics')['period_total_pulsations'];

        $this->assertSame(500, (int) $total($marzo25));
        $this->assertSame(900, (int) $total($abril25));
        $this->assertSame(111, (int) $total($marzo24));

        // Y las tres claves existen por separado.
        $this->assertTrue(Cache::has(KeyCounterCache::claveGrafica(2025, 3)));
        $this->assertTrue(Cache::has(KeyCounterCache::claveGrafica(2025, 4)));
        $this->assertTrue(Cache::has(KeyCounterCache::claveGrafica(2024, 3)));
    }

    #[Test]
    public function el_mes_en_curso_y_el_anterior_no_se_guardan_para_siempre(): void
    {
        $ahora = now();
        $anterior = $ahora->copy()->subMonthNoOverflow();

        $this->assertFalse(KeyCounterCache::esPeriodoCerrado($ahora->year, $ahora->month));
        $this->assertFalse(KeyCounterCache::esPeriodoCerrado($anterior->year, $anterior->month));

        // Y uno de hace tres meses sí.
        $viejo = $ahora->copy()->subMonthsNoOverflow(3);

        $this->assertTrue(KeyCounterCache::esPeriodoCerrado($viejo->year, $viejo->month));
    }

    /**
     * Una racha nueva tiene que verse: si no, el contador sube y la web no.
     */
    #[Test]
    public function una_racha_nueva_invalida_el_mes_en_curso(): void
    {
        $this->racha(now()->format('Y-m-d H:i:s'), 100);

        $this->get('/keycounter')->assertOk();

        $clave = KeyCounterCache::claveGrafica((int) date('Y'), (int) date('n'));

        $this->assertTrue(Cache::has($clave));

        app(KeyCounterService::class)->storeKeyboard([
            'hardware_device_id' => $this->device->id,
            'start_at' => now()->format('Y-m-d H:i:s'),
            'end_at' => now()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'pulsations' => 400,
            'pulsations_special_keys' => 2,
            'pulsation_average' => 6.6,
            'score' => 20,
            'weekday' => 0,
        ]);

        $this->assertFalse(
            Cache::has($clave),
            'Al guardar una racha hay que olvidar la gráfica del mes en curso.'
        );
    }

    /**
     * Una racha puede llegar tarde —un cacharro que estuvo sin red— y el día 1
     * eso cae en el mes pasado.
     */
    #[Test]
    public function una_racha_nueva_invalida_tambien_el_mes_anterior(): void
    {
        $anterior = now()->subMonthNoOverflow();
        $clave = KeyCounterCache::claveGrafica($anterior->year, $anterior->month);

        Cache::put($clave, 'lo que fuera', 900);

        KeyCounterCache::olvidarLoAfectadoPorUnaRachaNueva();

        $this->assertFalse(Cache::has($clave));
    }

    /**
     * El comando de precalentado deja hechos los meses cerrados y no toca los
     * abiertos, que caducan en un cuarto de hora.
     */
    #[Test]
    public function el_precalentado_solo_toca_los_meses_cerrados(): void
    {
        $this->racha('2025-03-15 10:00:00', 500);
        $this->racha(now()->format('Y-m-d H:i:s'), 100);

        $this->artisan('keycounter:warm_cache')->assertSuccessful();

        $this->assertTrue(Cache::has(KeyCounterCache::claveGrafica(2025, 3)));
        $this->assertFalse(
            Cache::has(KeyCounterCache::claveGrafica((int) date('Y'), (int) date('n'))),
        );
    }

    #[Test]
    public function el_precalentado_sin_datos_no_revienta(): void
    {
        $this->artisan('keycounter:warm_cache')
            ->expectsOutputToContain('No hay rachas registradas')
            ->assertSuccessful();
    }
}
