<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Gdacs\GdacsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `gdacs:sync`.
 *
 * Los eventos de los fixtures son reales, sacados de la API de GDACS el
 * 2026-09-15 (ver docs/future/gdacs-api.md): el incendio cercano es
 * `WF1031998`, el mismo que llegó por Telegram vía el plugin de Home
 * Assistant del usuario (124,8 km reportados, 124,9 km calculados aquí).
 */
class GdacsSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un incendio a ~125 km de Chipiona (dentro del radio por defecto, 150 km)
     * y otro a ~804 km (fuera). Sólo el primero debe guardarse.
     */
    private function nearAndFarFeatures(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => ['type' => 'Point', 'coordinates' => [-5.0619, 36.5202]],
                    'properties' => [
                        'eventtype' => 'WF',
                        'eventid' => 1031998,
                        'episodeid' => 1,
                        'name' => 'Forest fires in Spain',
                        'alertlevel' => 'Green',
                        'iscurrent' => 'true',
                        'fromdate' => '2026-09-13T00:00:00',
                        'todate' => '2026-09-14T00:00:00',
                        'datemodified' => '2026-09-15T13:00:51',
                        'country' => 'Spain',
                        'severitydata' => [
                            'severity' => 1109.0,
                            'severityunit' => 'ha',
                            'severitytext' => 'Green impact for forestfire in 1109 ha',
                        ],
                        'url' => ['report' => 'https://www.gdacs.org/report.aspx?eventid=1031998&episodeid=1&eventtype=WF'],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'geometry' => ['type' => 'Point', 'coordinates' => [-0.7181, 42.4725]],
                    'properties' => [
                        'eventtype' => 'WF',
                        'eventid' => 1030410,
                        'episodeid' => 1,
                        'name' => 'Forest fires in Spain',
                        'alertlevel' => 'Orange',
                        'iscurrent' => 'false',
                        'fromdate' => '2026-08-10T00:00:00',
                        'todate' => '2026-08-17T00:00:00',
                        'datemodified' => '2026-08-17T10:00:00',
                        'country' => 'Spain',
                        'severitydata' => [
                            'severity' => 5000.0,
                            'severityunit' => 'ha',
                            'severitytext' => 'Orange impact for forestfire in 5000 ha',
                        ],
                        'url' => ['report' => 'https://www.gdacs.org/report.aspx?eventid=1030410&episodeid=1&eventtype=WF'],
                    ],
                ],
            ],
        ];
    }

    private function fakeGdacsSearch(array $payload, int $status = 200): void
    {
        Http::fake([
            config('gdacs.base_url').'*' => Http::response($payload, $status),
        ]);
    }

    #[Test]
    public function it_keeps_only_events_within_the_configured_radius(): void
    {
        $this->fakeGdacsSearch($this->nearAndFarFeatures());

        $this->artisan('gdacs:sync')->assertExitCode(0);

        $this->assertSame(1, GdacsEvent::count());

        $event = GdacsEvent::first();
        $this->assertSame(1031998, $event->event_id);
        $this->assertSame('WF', $event->event_type->value);
        $this->assertSame('Green', $event->alert_level->value);
        $this->assertTrue($event->is_current);
        $this->assertEqualsWithDelta(124.9, $event->distance_km, 0.5);
        $this->assertSame(1109.0, $event->severity_value);
        $this->assertSame('ha', $event->severity_unit);
    }

    #[Test]
    public function iscurrent_string_true_false_casts_to_a_real_boolean(): void
    {
        // El evento lejano (fuera de radio) llega con iscurrent "false"; el
        // cercano con "true". Se comprueba con el cercano, que es el único
        // que se guarda.
        $this->fakeGdacsSearch($this->nearAndFarFeatures());

        $this->artisan('gdacs:sync');

        $this->assertTrue(GdacsEvent::first()->is_current);
    }

    #[Test]
    public function running_it_twice_updates_instead_of_duplicating(): void
    {
        $this->fakeGdacsSearch($this->nearAndFarFeatures());

        $this->artisan('gdacs:sync')->assertExitCode(0);
        $this->artisan('gdacs:sync')->assertExitCode(0);

        $this->assertSame(1, GdacsEvent::count());
    }

    #[Test]
    public function a_204_response_does_not_crash_the_command(): void
    {
        $this->fakeGdacsSearch([], 204);

        $this->artisan('gdacs:sync')->assertExitCode(0);

        $this->assertSame(0, GdacsEvent::count());
    }

    #[Test]
    public function a_malformed_response_does_not_crash_the_command(): void
    {
        Http::fake([
            config('gdacs.base_url').'*' => Http::response(['unexpected' => 'shape'], 200),
        ]);

        $this->artisan('gdacs:sync')->assertExitCode(0);

        $this->assertSame(0, GdacsEvent::count());
    }

    #[Test]
    public function an_event_with_an_unknown_type_or_alert_level_is_skipped(): void
    {
        $payload = $this->nearAndFarFeatures();
        $payload['features'][0]['properties']['eventtype'] = 'XX';

        $this->fakeGdacsSearch($payload);

        $this->artisan('gdacs:sync')->assertExitCode(0);

        $this->assertSame(0, GdacsEvent::count());
    }
}
