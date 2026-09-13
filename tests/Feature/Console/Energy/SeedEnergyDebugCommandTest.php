<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Energy;

use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyHistorical;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\Hardware\HardwareEnergyToday;
use App\Models\User;
use Database\Seeders\HardwareTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `debug:seed-energy` llena el panel con datos de mentira para poder mirarlo.
 *
 * Es un comando de desarrollo, pero se quedó escribiendo en el esquema viejo:
 * insertaba `read_at` —columna que el esquema unificado suprimió a propósito—
 * y creaba treinta filas de `hardware_energy_historical` por elemento, una por
 * día, cuando esa tabla guarda **un acumulado por sesión**. Reventaba con
 * `column "read_at" does not exist` sin que nada lo avisara, porque ningún test
 * lo ejecutaba.
 *
 * Estos tests no comprueban los valores —son aleatorios— sino que el comando
 * escriba en la forma que tiene el esquema.
 */
class SeedEnergyDebugCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(HardwareTypesSeeder::class);

        User::factory()->create();
    }

    #[Test]
    public function it_runs_without_blowing_up(): void
    {
        $this->artisan('debug:seed-energy', ['--devices' => 2, '--records' => 3])
            ->assertExitCode(0);

        $this->assertSame(2, HardwareEnergy::query()->count());
        $this->assertSame(6, HardwareEnergyReading::query()->count(), '3 lecturas por elemento.');
    }

    #[Test]
    public function it_writes_one_daily_summary_per_element_and_day(): void
    {
        $this->artisan('debug:seed-energy', ['--devices' => 2, '--records' => 1])
            ->assertExitCode(0);

        // 30 días por elemento, sin repetir ninguno.
        $this->assertSame(60, HardwareEnergyToday::query()->count());

        $this->assertSame(
            60,
            HardwareEnergyToday::query()
                ->toBase()
                ->selectRaw('hardware_energy_id, date')
                ->groupBy('hardware_energy_id', 'date')
                ->get()
                ->count(),
            'Ni un par (elemento, fecha) repetido.'
        );
    }

    #[Test]
    public function it_writes_a_single_accumulator_per_element(): void
    {
        $this->artisan('debug:seed-energy', ['--devices' => 3, '--records' => 1])
            ->assertExitCode(0);

        $acumulados = HardwareEnergyHistorical::query()->get();

        $this->assertCount(3, $acumulados, 'Un acumulado por elemento, no uno por día.');
        $this->assertSame([1, 1, 1], $acumulados->pluck('session_index')->all());
        $this->assertSame([30, 30, 30], $acumulados->pluck('days_operating')->all());
    }

    #[Test]
    public function the_accumulator_matches_the_sum_of_its_daily_summaries(): void
    {
        $this->artisan('debug:seed-energy', ['--devices' => 1, '--records' => 1])
            ->assertExitCode(0);

        $elemento = HardwareEnergy::query()->firstOrFail();

        $sumaDiaria = (float) HardwareEnergyToday::query()
            ->where('hardware_energy_id', $elemento->id)
            ->sum('energy_wh');

        $acumulado = (float) HardwareEnergyHistorical::query()
            ->where('hardware_energy_id', $elemento->id)
            ->value('energy_wh');

        $this->assertEqualsWithDelta($sumaDiaria, $acumulado, 0.01);
    }

    #[Test]
    public function running_it_twice_does_not_duplicate_anything(): void
    {
        $this->artisan('debug:seed-energy', ['--devices' => 2, '--records' => 1])->assertExitCode(0);
        $this->artisan('debug:seed-energy', ['--devices' => 2, '--records' => 1])->assertExitCode(0);

        $this->assertSame(2, HardwareEnergy::query()->count());
        $this->assertSame(60, HardwareEnergyToday::query()->count());
        $this->assertSame(2, HardwareEnergyHistorical::query()->count());
    }

    #[Test]
    public function it_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        $this->artisan('debug:seed-energy', ['--devices' => 1, '--records' => 1])
            ->assertExitCode(1);

        $this->assertSame(0, HardwareEnergyReading::query()->count());
    }
}
