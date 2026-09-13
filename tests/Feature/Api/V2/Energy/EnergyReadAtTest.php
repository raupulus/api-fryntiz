<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2\Energy;

use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

/**
 * `read_at`: cuándo tomó el aparato la muestra.
 *
 * Por defecto la marca de tiempo de una lectura es la hora a la que llegó al
 * servidor. Para un aparato que sube cada minuto da igual, pero tras un corte de
 * red un reintento guardaba media hora de muestras **todas con la hora del
 * reintento**, y con eso la curva del día queda plana durante el corte y con un
 * pico al final.
 *
 * Los aparatos que llevan reloj sincronizado —el Renogy con su Pico por NTP—
 * pueden mandar `read_at` y entonces manda esa hora. Los que no llevan reloj no
 * mandan nada y todo sigue igual que antes.
 *
 * Afecta a dos cosas, no a una: a la marca de la lectura **y al día en el que
 * cae su resumen**. Una muestra de ayer reenviada hoy suma en el resumen de
 * ayer, que es donde ocurrió.
 */
class EnergyReadAtTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private User $user;

    private HardwareDevice $device;

    private HardwareEnergy $elemento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser(3);
        $this->device = HardwareDevice::create(['user_id' => $this->user->id, 'name' => 'Nodo con reloj']);

        $this->elemento = HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_LOAD,
            'sensor_position' => 0,
            'nominal_voltage' => 12.0,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function subir(array $extra = []): TestResponse
    {
        return $this->postJson(
            $this->apiUrl('energy/readings'),
            array_merge([
                'hardware_device_id' => $this->device->id,
                'duration' => 600,
                'energy' => ['loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]]],
            ], $extra),
            $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
        );
    }

    private function lectura(): HardwareEnergyReading
    {
        return HardwareEnergyReading::query()->firstOrFail();
    }

    #[Test]
    public function without_read_at_the_timestamp_is_the_one_of_arrival(): void
    {
        $this->travelTo(Carbon::parse('2026-09-13 10:00:00', 'UTC'), function (): void {
            $this->subir()->assertStatus(201);
        });

        $this->assertSame(
            '2026-09-13 10:00:00',
            $this->lectura()->created_at->utc()->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function read_at_replaces_the_timestamp_of_the_reading(): void
    {
        $this->travelTo(Carbon::parse('2026-09-13 10:00:00', 'UTC'), function (): void {
            $this->subir(['read_at' => '2026-09-13T08:15:00Z'])->assertStatus(201);
        });

        $this->assertSame(
            '2026-09-13 08:15:00',
            $this->lectura()->created_at->utc()->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function read_at_also_works_inside_the_energy_block(): void
    {
        // Igual que `duration`: vale en la raíz o dentro de `energy`.
        $this->travelTo(Carbon::parse('2026-09-13 10:00:00', 'UTC'), function (): void {
            $this->postJson(
                $this->apiUrl('energy/readings'),
                [
                    'hardware_device_id' => $this->device->id,
                    'energy' => [
                        'duration' => 600,
                        'read_at' => '2026-09-13T08:15:00Z',
                        'loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]],
                    ],
                ],
                $this->moduleHeaders($this->user, TokenAbilities::ENERGY_WRITE)
            )->assertStatus(201);
        });

        $this->assertSame(
            '2026-09-13 08:15:00',
            $this->lectura()->created_at->utc()->format('Y-m-d H:i:s')
        );
    }

    #[Test]
    public function the_response_returns_the_timestamp_of_the_sample(): void
    {
        $respuesta = $this->subir(['read_at' => '2026-09-13T08:15:00Z']);

        $respuesta->assertStatus(201);
        $this->assertStringStartsWith('2026-09-13T08:15:00', (string) $respuesta->json('data.0.created_at'));
    }

    #[Test]
    public function a_sample_from_yesterday_lands_in_yesterdays_summary(): void
    {
        // El caso que justifica el campo: se reenvía hoy una muestra de ayer y
        // tiene que sumar en el día en el que ocurrió, no en el de hoy.
        $this->travelTo(Carbon::parse('2026-09-13 10:00:00', 'UTC'), function (): void {
            $this->subir(['read_at' => '2026-09-12T23:40:00Z'])->assertStatus(201);
        });

        $resumen = HardwareEnergyToday::query()->firstOrFail();

        $this->assertSame('2026-09-12', $resumen->date->format('Y-m-d'));
        $this->assertEqualsWithDelta(2.0, (float) $resumen->energy_wh, 0.0001);
    }

    #[Test]
    public function two_samples_of_different_days_go_to_different_summaries(): void
    {
        $this->travelTo(Carbon::parse('2026-09-13 10:00:00', 'UTC'), function (): void {
            $this->subir(['read_at' => '2026-09-12T23:40:00Z'])->assertStatus(201);
            $this->subir(['read_at' => '2026-09-13T00:10:00Z'])->assertStatus(201);
        });

        $this->assertSame(
            ['2026-09-12', '2026-09-13'],
            HardwareEnergyToday::query()
                ->orderBy('date')
                ->get()
                ->map(static fn (HardwareEnergyToday $r): string => $r->date->format('Y-m-d'))
                ->all()
        );
    }

    #[Test]
    public function a_timestamp_in_the_future_is_rejected(): void
    {
        // Un reloj mal puesto metería lecturas en días que aún no existen, y el
        // cierre nocturno nunca volvería a pasar por ellos.
        $this->subir(['read_at' => Carbon::now('UTC')->addDays(2)->toIso8601String()])
            ->assertStatus(422);

        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }

    #[Test]
    public function an_absurdly_old_timestamp_is_rejected(): void
    {
        $this->subir(['read_at' => '1970-01-01T00:00:00Z'])->assertStatus(422);

        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }

    #[Test]
    public function something_that_is_not_a_date_is_rejected(): void
    {
        $this->subir(['read_at' => 'ayer por la tarde'])->assertStatus(422);

        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }

    #[Test]
    public function a_small_clock_drift_into_the_future_is_tolerated(): void
    {
        // Un aparato con el reloj unos minutos adelantado no debería perder sus
        // lecturas: se acepta hasta una hora de desfase.
        $this->subir(['read_at' => Carbon::now('UTC')->addMinutes(5)->toIso8601String()])
            ->assertStatus(201);

        $this->assertSame(1, HardwareEnergyReading::query()->count());
    }

    #[Test]
    public function read_at_applies_to_the_three_blocks_of_the_same_upload(): void
    {
        HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
            'nominal_voltage' => 24.0,
            'is_active' => true,
        ]);

        $this->subir([
            'read_at' => '2026-09-13T08:15:00Z',
            'energy' => [
                'generator' => ['voltage' => 24.0, 'amperage' => 2.0],
                'loads' => [['channel' => 0, 'voltage' => 12.0, 'amperage' => 1.0]],
            ],
        ])->assertStatus(201);

        $marcas = HardwareEnergyReading::query()
            ->get()
            ->map(static fn (HardwareEnergyReading $r): string => $r->created_at->utc()->format('Y-m-d H:i:s'))
            ->unique()
            ->values()
            ->all();

        $this->assertSame(['2026-09-13 08:15:00'], $marcas, 'Una subida es una muestra: una sola hora.');
    }
}
