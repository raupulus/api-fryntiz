<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Persistence;

use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\AirFlight\AirFlightRoute;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Un ICAO identifica a un único avión: el índice de `icao` es único (en
 * producción había tres pares duplicados por peticiones simultáneas) y
 * `searchHex()` sólo resuelve ICAO de verdad (6 dígitos hexadecimales), no
 * las direcciones `~xxxxxx` de TIS-B.
 */
class AirFlightIcaoIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_database_rejects_two_airplanes_with_the_same_icao(): void
    {
        AirFlightAirPlane::create(['icao' => '348205']);

        $this->expectException(UniqueConstraintViolationException::class);

        AirFlightAirPlane::create(['icao' => '348205']);
    }

    #[Test]
    public function search_hex_only_resolves_real_icao_addresses(): void
    {
        $this->assertSame('Spain', AirFlightAirPlane::searchHex('348205')['country']);
        $this->assertSame('Spain', AirFlightAirPlane::searchHex('34820A')['country']);

        $this->assertNull(AirFlightAirPlane::searchHex('~348205'));
        $this->assertNull(AirFlightAirPlane::searchHex('34820'));
        $this->assertNull(AirFlightAirPlane::searchHex(''));
        $this->assertNull(AirFlightAirPlane::searchHex(null));
    }

    #[Test]
    public function the_migration_merges_existing_duplicates_before_making_icao_unique(): void
    {
        require_once database_path('migrations/2026_09_19_000001_make_airflight_airplanes_icao_unique.php');

        // Estado previo a la migración: índice normal, con duplicados dentro.
        Schema::table('airflight_airplanes', function ($table) {
            $table->dropUnique('airflight_airplanes_icao_unique');
            $table->index('icao', 'airflight_airplanes_icao_idx');
        });

        $first = AirFlightAirPlane::create([
            'icao' => '348311',
            'seen_first_at' => '2026-07-06 07:24:24',
            'seen_last_at' => '2026-07-06 07:24:24',
        ]);
        $second = AirFlightAirPlane::create([
            'icao' => '348311',
            'registration' => 'EC-OMT',
            'seen_first_at' => '2026-07-06 07:24:23',
            'seen_last_at' => '2026-07-06 07:25:37',
        ]);
        $route = AirFlightRoute::create(['airplane_id' => $second->id, 'seen_at' => '2026-07-06 07:25:37']);
        $other = AirFlightAirPlane::create(['icao' => '3483c9']);

        (new \MakeAirFlightAirplanesIcaoUnique)->up();

        $this->assertSame(1, AirFlightAirPlane::where('icao', '348311')->count());
        $this->assertNull(AirFlightAirPlane::find($second->id));

        $kept = AirFlightAirPlane::find($first->id);
        $this->assertSame('EC-OMT', $kept->registration);
        $this->assertSame('2026-07-06 07:24:23', $kept->seen_first_at);
        $this->assertSame('2026-07-06 07:25:37', $kept->seen_last_at);
        $this->assertSame($first->id, $route->fresh()->airplane_id);
        $this->assertNotNull(AirFlightAirPlane::find($other->id));

        $this->expectException(UniqueConstraintViolationException::class);
        AirFlightAirPlane::create(['icao' => '348311']);
    }
}
