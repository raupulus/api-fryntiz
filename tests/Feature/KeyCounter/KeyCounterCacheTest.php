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
 * La caché de las estadísticas de KeyCounter.
 *
 * `getStatisticsPreparedToGraphics()` era lo único caro de la página que no
 * estaba cacheado: agrega todas las rachas del mes por día y por dispositivo.
 * Sobre el volcado real tarda más de un segundo.
 *
 * Aquí se prueban tres cosas distintas, y conviene no confundirlas:
 *
 *  1. Que cada mes tenga su entrada (el fallo más fácil de cometer y el más
 *     difícil de ver: la página funciona, sólo que enseña otro mes).
 *  2. Que el visitante **no** pague el cálculo: lo escribe el planificador.
 *  3. Que una racha recién subida **no** se vea hasta el siguiente refresco.
 *     Eso es privacidad, no rendimiento, y es la razón de que la ingesta ya no
 *     invalide nada.
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

    private function streak(string $when, int $pulsations): void
    {
        $streak = Keyboard::create([
            'hardware_device_id' => $this->device->id,
            'start_at' => $when,
            'end_at' => $when,
            'duration' => 300,
            'pulsations' => $pulsations,
            'pulsations_special_keys' => 1,
            'pulsation_average' => 2.0,
            'score' => 10,
            'weekday' => 0,
        ]);

        // El resumen agrupa por `created_at`, que no está en `$fillable`.
        $streak->forceFill(['created_at' => $when])->save();
    }

    /**
     * @return array{int, mixed}
     */
    private function withQueryCount(callable $action): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $action();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return [$queries, $result];
    }

    /**
     * Pulsaciones que enseña la página para el mes que se le pida.
     */
    private function shownTotal(?int $month = null, ?int $year = null): int
    {
        $month ??= (int) date('n');
        $year ??= (int) date('Y');

        $response = $this->get("/keycounter?month={$month}&year={$year}")->assertOk();

        return (int) $response->viewData('keyboard_statistics')['period_total_pulsations'];
    }

    #[Test]
    public function the_second_visit_to_a_closed_month_queries_nothing(): void
    {
        $this->streak('2025-03-15 10:00:00', 500);

        [$first] = $this->withQueryCount(
            fn () => $this->get('/keycounter?month=3&year=2025')->assertOk()
        );

        [$second] = $this->withQueryCount(
            fn () => $this->get('/keycounter?month=3&year=2025')->assertOk()
        );

        $this->assertGreaterThan(0, $first);
        $this->assertLessThan(
            $first,
            $second,
            'La segunda visita debería salir de la caché y consultar menos.'
        );
    }

    /**
     * El fallo que rompería la página de verdad: que un mes enseñe los datos de
     * otro porque la clave no los distingue.
     */
    #[Test]
    public function each_month_has_its_own_entry(): void
    {
        $this->streak('2025-03-15 10:00:00', 500);
        $this->streak('2025-04-15 10:00:00', 900);
        $this->streak('2024-03-15 10:00:00', 111);

        $this->assertSame(500, $this->shownTotal(3, 2025));
        $this->assertSame(900, $this->shownTotal(4, 2025));
        $this->assertSame(111, $this->shownTotal(3, 2024));

        // Y las tres claves existen por separado.
        $this->assertTrue(Cache::has(KeyCounterCache::graphCacheKey(2025, 3)));
        $this->assertTrue(Cache::has(KeyCounterCache::graphCacheKey(2025, 4)));
        $this->assertTrue(Cache::has(KeyCounterCache::graphCacheKey(2024, 3)));
    }

    #[Test]
    public function the_current_and_previous_month_are_not_stored_forever(): void
    {
        $now = now();
        $previous = $now->copy()->subMonthNoOverflow();

        $this->assertFalse(KeyCounterCache::isPeriodClosed($now->year, $now->month));
        $this->assertFalse(KeyCounterCache::isPeriodClosed($previous->year, $previous->month));

        // Y uno de hace tres meses sí.
        $old = $now->copy()->subMonthsNoOverflow(3);

        $this->assertTrue(KeyCounterCache::isPeriodClosed($old->year, $old->month));
    }

    /**
     * Privacidad: la web no refleja la actividad en tiempo real.
     *
     * Hasta el 2026-09-10 la ingesta invalidaba la caché, así que la primera
     * visita después de cada subida enseñaba la racha recién llegada y la
     * ventana no existía en la práctica.
     */
    #[Test]
    public function a_new_streak_is_not_visible_until_the_next_refresh(): void
    {
        $this->streak(now()->format('Y-m-d H:i:s'), 100);

        $this->assertSame(100, $this->shownTotal());

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

        $this->assertSame(
            100,
            $this->shownTotal(),
            'Una racha recién subida no puede verse hasta el siguiente refresco.'
        );

        // Y en cuanto pasa el refresco horario, sí.
        $this->artisan('keycounter:warm_cache', ['--live' => true])->assertSuccessful();

        $this->assertSame(500, $this->shownTotal());
    }

    /**
     * El refresco escribe el mes en curso sin que nadie haya entrado: eso es lo
     * que hace que la ventana de una hora no la pague el visitante.
     */
    #[Test]
    public function the_live_warm_up_writes_the_open_months_with_no_visit(): void
    {
        $this->streak(now()->format('Y-m-d H:i:s'), 250);
        $this->streak(now()->subMonthNoOverflow()->format('Y-m-d H:i:s'), 700);

        $current = KeyCounterCache::graphCacheKey((int) date('Y'), (int) date('n'));
        $previous = now()->subMonthNoOverflow();

        $this->assertFalse(Cache::has($current));

        $this->artisan('keycounter:warm_cache', ['--live' => true])->assertSuccessful();

        $this->assertTrue(Cache::has($current));
        $this->assertTrue(Cache::has(KeyCounterCache::graphCacheKey($previous->year, $previous->month)));
        $this->assertTrue(Cache::has(KeyCounterCache::KEYBOARD_SUMMARY_KEY));
        $this->assertTrue(Cache::has(KeyCounterCache::MOUSE_SUMMARY_KEY));
        $this->assertTrue(Cache::has(KeyCounterCache::WIDGETS_KEY));
        $this->assertTrue(Cache::has(KeyCounterCache::yearTotalCacheKey((int) date('Y'))));
    }

    /**
     * Y con eso hecho, la visita no calcula: sólo lee.
     */
    #[Test]
    public function a_visit_after_the_live_warm_up_is_cheaper(): void
    {
        $this->streak(now()->format('Y-m-d H:i:s'), 250);

        [$cold] = $this->withQueryCount(fn () => $this->get('/keycounter')->assertOk());

        Cache::flush();
        $this->artisan('keycounter:warm_cache', ['--live' => true])->assertSuccessful();

        [$warm] = $this->withQueryCount(fn () => $this->get('/keycounter')->assertOk());

        $this->assertLessThan(
            $cold,
            $warm,
            'Con la caché ya escrita, la visita tiene que consultar menos.'
        );
    }

    /**
     * El pase diario deja hechos los meses cerrados y no toca los abiertos, que
     * son cosa del refresco horario.
     */
    #[Test]
    public function warming_up_only_touches_closed_months(): void
    {
        $this->streak('2025-03-15 10:00:00', 500);
        $this->streak(now()->format('Y-m-d H:i:s'), 100);

        $this->artisan('keycounter:warm_cache')->assertSuccessful();

        $this->assertTrue(Cache::has(KeyCounterCache::graphCacheKey(2025, 3)));
        $this->assertFalse(
            Cache::has(KeyCounterCache::graphCacheKey((int) date('Y'), (int) date('n'))),
        );
    }

    /**
     * `--force` es para después de tocar rachas históricas: lo que ya estaba
     * guardado «para siempre» hay que reescribirlo, no respetarlo.
     */
    #[Test]
    public function forcing_the_warm_up_rewrites_a_closed_month_already_cached(): void
    {
        $key = KeyCounterCache::graphCacheKey(2025, 3);

        $this->streak('2025-03-15 10:00:00', 500);
        Cache::forever($key, 'lo que fuera');

        $this->artisan('keycounter:warm_cache')->assertSuccessful();
        $this->assertSame('lo que fuera', Cache::get($key));

        $this->artisan('keycounter:warm_cache', ['--force' => true])->assertSuccessful();
        $this->assertIsArray(Cache::get($key));
    }

    /**
     * Un borrado de duplicados toca meses **cerrados**, y esos están guardados
     * para siempre: si no se invalidan, la gráfica mala se queda puesta.
     */
    #[Test]
    public function removing_duplicates_forgets_the_affected_months(): void
    {
        // Dos rachas idénticas en un mes cerrado: la segunda es el duplicado.
        $this->streak('2025-03-15 10:00:00', 500);
        $this->streak('2025-03-15 10:00:00', 500);

        $key = KeyCounterCache::graphCacheKey(2025, 3);

        $this->get('/keycounter?month=3&year=2025')->assertOk();
        $this->assertTrue(Cache::has($key));

        $this->artisan('keycounter:remove_duplicate', ['--force' => true, '--full' => true])
            ->assertSuccessful();

        $this->assertFalse(
            Cache::has($key),
            'Al borrar un duplicado de un mes cerrado hay que olvidar su gráfica.'
        );
        $this->assertFalse(Cache::has(KeyCounterCache::WIDGETS_KEY));
    }

    #[Test]
    public function forgetting_the_open_periods_drops_the_current_and_previous_month(): void
    {
        $previous = now()->subMonthNoOverflow();
        $keys = [
            KeyCounterCache::graphCacheKey((int) date('Y'), (int) date('n')),
            KeyCounterCache::graphCacheKey($previous->year, $previous->month),
        ];

        foreach ($keys as $key) {
            Cache::put($key, 'lo que fuera', KeyCounterCache::STORAGE_WINDOW);
        }

        KeyCounterCache::forgetOpenPeriods();

        foreach ($keys as $key) {
            $this->assertFalse(Cache::has($key));
        }
    }

    #[Test]
    public function warming_up_without_data_does_not_blow_up(): void
    {
        $this->artisan('keycounter:warm_cache')
            ->expectsOutputToContain('No hay rachas registradas')
            ->assertSuccessful();
    }
}
