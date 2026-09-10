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

    private AirFlightAirPlane $airplane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->airplane = AirFlightAirPlane::create(['icao' => 'DUPTEST', 'seen_last_at' => Carbon::now()]);
    }

    private function route(Carbon $seenAt, ?int $messages, array $extra = []): AirFlightRoute
    {
        return AirFlightRoute::create(array_merge([
            'airplane_id' => $this->airplane->id,
            'seen_at' => $seenAt,
            'messages' => $messages,
        ], $extra));
    }

    #[Test]
    public function without_force_it_detects_but_deletes_nothing(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $this->route($seenAt, 42);
        $this->route($seenAt, 42);
        $this->route($seenAt, 42);

        $this->artisan('airflight:remove_duplicate_routes')->assertExitCode(0);

        $this->assertSame(3, $this->airplane->routes()->count());
    }

    #[Test]
    public function with_force_it_keeps_only_one_row_per_duplicate_group_and_keeps_the_oldest(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $first = $this->route($seenAt, 42);
        $this->route($seenAt, 42);
        $this->route($seenAt, 42);

        $this->artisan('airflight:remove_duplicate_routes', ['--force' => true])->assertExitCode(0);

        $remaining = $this->airplane->routes()->get();
        $this->assertCount(1, $remaining);
        $this->assertSame($first->id, $remaining->first()->id);
    }

    #[Test]
    public function rows_with_a_different_messages_or_seen_at_are_not_touched(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $this->route($seenAt, 42);
        $this->route($seenAt, 43);
        $this->route($seenAt->copy()->addSecond(), 42);

        $this->artisan('airflight:remove_duplicate_routes', ['--force' => true])->assertExitCode(0);

        $this->assertSame(3, $this->airplane->routes()->count());
    }

    #[Test]
    public function the_date_parameter_limits_the_deletion_to_that_day(): void
    {
        $today = Carbon::now()->subMinutes(3);
        $yesterday = Carbon::now()->subDay();

        // Duplicado de hoy.
        $this->route($today, 42);
        $this->route($today, 42);

        // Duplicado de ayer: no debe tocarse al acotar por la fecha de hoy.
        $this->route($yesterday, 99);
        $this->route($yesterday, 99);

        $this->artisan('airflight:remove_duplicate_routes', [
            '--date' => Carbon::now()->toDateString(),
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, $this->airplane->routes()->where('messages', 42)->count());
        $this->assertSame(2, $this->airplane->routes()->where('messages', 99)->count());
    }

    #[Test]
    public function an_invalid_date_format_fails_without_touching_data(): void
    {
        $seenAt = Carbon::now()->subMinutes(3);
        $this->route($seenAt, 42);
        $this->route($seenAt, 42);

        $this->artisan('airflight:remove_duplicate_routes', [
            '--date' => '08-09-2026',
            '--force' => true,
        ])->assertExitCode(1);

        $this->assertSame(2, $this->airplane->routes()->count());
    }
}
