<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Widgets\EnergyRoleTrendChart;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use App\Models\Hardware\HardwareEnergyReading;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * La gráfica de cada papel: potencia media hora a hora de la última semana.
 */
class EnergyRoleTrendChartTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($user);

        $this->device = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Rover']);
    }

    private function element(string $role, int $channel = 0): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $this->device->id,
            'hardware_device_monitorized_id' => $this->device->id,
            'role' => $role,
            'sensor_position' => $channel,
        ]);
    }

    private function reading(HardwareEnergy $element, Carbon $when, float $power, bool $suspicious = false): void
    {
        HardwareEnergyReading::create([
            'hardware_device_id' => $this->device->id,
            'hardware_energy_id' => $element->id,
            'voltage' => 12.0,
            'amperage' => $power / 12,
            'power' => $power,
            'is_suspicious' => $suspicious,
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }

    /**
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    private function chartData(string $role): array
    {
        $chart = new EnergyRoleTrendChart;
        $chart->deviceId = $this->device->id;
        $chart->role = $role;

        /** @var array{datasets: array<int, array<string, mixed>>, labels: array<int, string>} $data */
        $data = (new ReflectionMethod($chart, 'getData'))->invoke($chart);

        return $data;
    }

    #[Test]
    public function covers_all_168_hours_of_the_week(): void
    {
        $this->element(HardwareEnergy::ROLE_GENERATOR);

        $this->assertCount(7 * 24, $this->chartData(HardwareEnergy::ROLE_GENERATOR)['labels']);
    }

    #[Test]
    public function plots_average_power_per_hour(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $hour = Carbon::now('UTC')->startOfHour()->subHours(3);

        $this->reading($panel, $hour->copy()->addMinutes(5), 100.0);
        $this->reading($panel, $hour->copy()->addMinutes(35), 200.0);

        $values = array_filter($this->chartData(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data']);

        $this->assertSame([150.0], array_values($values), 'La media de 100 y 200, no la suma.');
    }

    #[Test]
    public function leaves_hours_without_readings_as_null(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $this->reading($panel, Carbon::now('UTC')->startOfHour()->subHours(3)->addMinutes(5), 100.0);

        $values = $this->chartData(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data'];

        $this->assertContains(null, $values);
        $this->assertNotContains(0.0, $values);
    }

    #[Test]
    public function averages_across_all_load_channels(): void
    {
        $one = $this->element(HardwareEnergy::ROLE_LOAD, channel: 0);
        $two = $this->element(HardwareEnergy::ROLE_LOAD, channel: 1);
        $hour = Carbon::now('UTC')->startOfHour()->subHours(2);

        $this->reading($one, $hour->copy()->addMinutes(5), 10.0);
        $this->reading($two, $hour->copy()->addMinutes(5), 30.0);

        $values = array_values(array_filter($this->chartData(HardwareEnergy::ROLE_LOAD)['datasets'][0]['data']));

        $this->assertSame([20.0], $values, 'Media de los dos canales en esa hora.');
    }

    #[Test]
    public function ignores_suspicious_readings(): void
    {
        $panel = $this->element(HardwareEnergy::ROLE_GENERATOR);
        $hour = Carbon::now('UTC')->startOfHour()->subHours(2);

        $this->reading($panel, $hour->copy()->addMinutes(5), 100.0);
        $this->reading($panel, $hour->copy()->addMinutes(6), 9000.0, suspicious: true);

        $values = array_values(array_filter($this->chartData(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data']));

        $this->assertSame([100.0], $values);
    }

    #[Test]
    public function preserves_battery_sign(): void
    {
        $bank = $this->element(HardwareEnergy::ROLE_BATTERY);
        $this->reading($bank, Carbon::now('UTC')->startOfHour()->subHour()->addMinutes(5), -40.0);

        $values = array_values(array_filter(
            $this->chartData(HardwareEnergy::ROLE_BATTERY)['datasets'][0]['data'],
            static fn ($v) => $v !== null,
        ));

        $this->assertSame([-40.0], $values);
    }

    #[Test]
    public function isolates_readings_from_other_devices(): void
    {
        $mine = $this->element(HardwareEnergy::ROLE_GENERATOR);

        $other = HardwareDevice::create(['user_id' => $this->device->user_id, 'name' => 'Otro']);
        $otherElement = HardwareEnergy::create([
            'hardware_device_id' => $other->id,
            'hardware_device_monitorized_id' => $other->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
        ]);

        $hour = Carbon::now('UTC')->startOfHour()->subHour();
        $this->reading($mine, $hour->copy()->addMinutes(5), 100.0);

        HardwareEnergyReading::create([
            'hardware_device_id' => $other->id,
            'hardware_energy_id' => $otherElement->id,
            'power' => 5000.0,
            'created_at' => $hour->copy()->addMinutes(5),
            'updated_at' => $hour->copy()->addMinutes(5),
        ]);

        $values = array_values(array_filter($this->chartData(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data']));

        $this->assertSame([100.0], $values);
    }

    #[Test]
    public function each_role_has_heading_and_description(): void
    {
        $chart = new EnergyRoleTrendChart;

        foreach ([
            HardwareEnergy::ROLE_GENERATOR => 'Generador',
            HardwareEnergy::ROLE_BATTERY => 'Batería',
            HardwareEnergy::ROLE_LOAD => 'Consumo',
        ] as $role => $expected) {
            $chart->role = $role;

            $this->assertStringStartsWith($expected, $chart->getHeading());
            $this->assertNotNull($chart->getDescription());
        }
    }

    #[Test]
    public function is_not_auto_discovered_on_dashboard(): void
    {
        $this->assertFalse(EnergyRoleTrendChart::isDiscovered());
    }
}
