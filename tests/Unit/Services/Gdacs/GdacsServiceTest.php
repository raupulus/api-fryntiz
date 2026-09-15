<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Gdacs;

use App\Services\Gdacs\GdacsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `GdacsService::distanceKm()` contra distancias reales verificadas a mano
 * (ver docs/future/archived/gdacs-api.md): Chipiona → WF1031998 (Málaga) son 124,8-
 * 124,9 km según la propia GDACS/el plugin de Home Assistant del usuario.
 */
class GdacsServiceTest extends TestCase
{
    private const CHIPIONA_LAT = 36.7371;

    private const CHIPIONA_LON = -6.4348;

    #[Test]
    public function distance_between_two_identical_points_is_zero(): void
    {
        $distance = GdacsService::distanceKm(self::CHIPIONA_LAT, self::CHIPIONA_LON, self::CHIPIONA_LAT, self::CHIPIONA_LON);

        $this->assertEqualsWithDelta(0.0, $distance, 0.001);
    }

    #[Test]
    public function distance_to_wf1031998_matches_the_real_alert(): void
    {
        // Málaga, 36.5202, -5.0619 — el incendio del aviso real de Telegram.
        $distance = GdacsService::distanceKm(self::CHIPIONA_LAT, self::CHIPIONA_LON, 36.5202, -5.0619);

        $this->assertEqualsWithDelta(124.9, $distance, 0.5);
    }

    #[Test]
    public function distance_to_a_far_event_exceeds_the_default_radius(): void
    {
        // Pirineo aragonés, 42.4725, -0.7181 — a más de 150 km.
        $distance = GdacsService::distanceKm(self::CHIPIONA_LAT, self::CHIPIONA_LON, 42.4725, -0.7181);

        $this->assertGreaterThan(150.0, $distance);
        $this->assertEqualsWithDelta(803.7, $distance, 1.0);
    }
}
