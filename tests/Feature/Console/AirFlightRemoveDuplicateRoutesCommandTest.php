<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `airflight:remove_duplicate_routes` limpia subidas repetidas del mismo
 * sondeo: mismo avión, mismo instante (`seen_at`) y mismo contador de
 * mensajes (`messages`). No es la prevención —eso ya lo hace la fusión por
 * `messages` de `AirFlightService::addAircraft()`—, es la limpieza de lo
 * que quedó guardado antes de ese fix.
 *
 * Lo que más importa aquí es que, sin `--force`, no borre nada.
 */
class AirFlightRemoveDuplicateRoutesCommandTest extends TestCase
{
    use RefreshDatabase;

    private AirFlightAirPlane $avion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->avion = AirFlightAirPlane::create(['icao' => 'DUPTEST', 'seen_last_at' => Carbon::now()]);
    }

    private function ruta(Carbon $seenAt, ?int $messages, array $extra = []): AirFlightRoute
    {
        return AirFlightRoute::create(array_merge([
            'airplane_id' => $this->avion->id,
            'seen_at' => $seenAt,
            'messages' => $messages,
        ], $extra));
    }

    #[Test]
    public function sin_force_detecta_pero_no_borra_nada(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $this->ruta($seenAt, 42);
        $this->ruta($seenAt, 42);
        $this->ruta($seenAt, 42);

        $this->artisan('airflight:remove_duplicate_routes')->assertExitCode(0);

        $this->assertSame(3, $this->avion->routes()->count());
    }

    #[Test]
    public function con_force_deja_solo_una_fila_por_grupo_duplicado_y_conserva_la_mas_antigua(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $primera = $this->ruta($seenAt, 42);
        $this->ruta($seenAt, 42);
        $this->ruta($seenAt, 42);

        $this->artisan('airflight:remove_duplicate_routes', ['--force' => true])->assertExitCode(0);

        $restantes = $this->avion->routes()->get();
        $this->assertCount(1, $restantes);
        $this->assertSame($primera->id, $restantes->first()->id);
    }

    #[Test]
    public function filas_con_distinto_messages_o_seen_at_no_se_tocan(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $this->ruta($seenAt, 42);
        $this->ruta($seenAt, 43);
        $this->ruta($seenAt->copy()->addSecond(), 42);

        $this->artisan('airflight:remove_duplicate_routes', ['--force' => true])->assertExitCode(0);

        $this->assertSame(3, $this->avion->routes()->count());
    }

    #[Test]
    public function el_parametro_date_acota_el_borrado_a_ese_dia(): void
    {
        $hoy = Carbon::now()->subMinutes(3);
        $ayer = Carbon::now()->subDay();

        // Duplicado de hoy.
        $this->ruta($hoy, 42);
        $this->ruta($hoy, 42);

        // Duplicado de ayer: no debe tocarse al acotar por la fecha de hoy.
        $this->ruta($ayer, 99);
        $this->ruta($ayer, 99);

        $this->artisan('airflight:remove_duplicate_routes', [
            '--date' => Carbon::now()->toDateString(),
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, $this->avion->routes()->where('messages', 42)->count());
        $this->assertSame(2, $this->avion->routes()->where('messages', 99)->count());
    }

    #[Test]
    public function una_fecha_con_formato_invalido_falla_sin_tocar_datos(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $this->ruta($seenAt, 42);
        $this->ruta($seenAt, 42);

        $this->artisan('airflight:remove_duplicate_routes', [
            '--date' => '08-09-2026',
            '--force' => true,
        ])->assertExitCode(1);

        $this->assertSame(2, $this->avion->routes()->count());
    }
}
