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
 *
 * Lo que se viene a mirar es la forma del día —a qué hora arranca el panel,
 * cuándo pega el pico, si el consumo es plano—, así que se dibuja potencia y no
 * energía acumulada.
 */
class EnergyRoleTrendChartTest extends TestCase
{
    use RefreshDatabase;

    private HardwareDevice $aparato;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $user = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $this->actingAs($user);

        $this->aparato = HardwareDevice::create(['user_id' => $user->id, 'name' => 'Rover']);
    }

    private function elemento(string $papel, int $canal = 0): HardwareEnergy
    {
        return HardwareEnergy::create([
            'hardware_device_id' => $this->aparato->id,
            'hardware_device_monitorized_id' => $this->aparato->id,
            'role' => $papel,
            'sensor_position' => $canal,
        ]);
    }

    private function lectura(HardwareEnergy $elemento, Carbon $cuando, float $potencia, bool $sospechosa = false): void
    {
        HardwareEnergyReading::create([
            'hardware_device_id' => $this->aparato->id,
            'hardware_energy_id' => $elemento->id,
            'voltage' => 12.0,
            'amperage' => $potencia / 12,
            'power' => $potencia,
            'is_suspicious' => $sospechosa,
            'created_at' => $cuando,
            'updated_at' => $cuando,
        ]);
    }

    /**
     * @return array{datasets: array<int, array<string, mixed>>, labels: array<int, string>}
     */
    private function datos(string $papel): array
    {
        $grafica = new EnergyRoleTrendChart;
        $grafica->deviceId = $this->aparato->id;
        $grafica->papel = $papel;

        /** @var array{datasets: array<int, array<string, mixed>>, labels: array<int, string>} $datos */
        $datos = (new ReflectionMethod($grafica, 'getData'))->invoke($grafica);

        return $datos;
    }

    #[Test]
    public function son_las_168_horas_de_la_semana(): void
    {
        $this->elemento(HardwareEnergy::ROLE_GENERATOR);

        $this->assertCount(7 * 24, $this->datos(HardwareEnergy::ROLE_GENERATOR)['labels']);
    }

    #[Test]
    public function dibuja_la_potencia_media_de_cada_hora(): void
    {
        $panel = $this->elemento(HardwareEnergy::ROLE_GENERATOR);
        $hora = Carbon::now('UTC')->startOfHour()->subHours(3);

        $this->lectura($panel, $hora->copy()->addMinutes(5), 100.0);
        $this->lectura($panel, $hora->copy()->addMinutes(35), 200.0);

        $valores = array_filter($this->datos(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data']);

        $this->assertSame([150.0], array_values($valores), 'La media de 100 y 200, no la suma.');
    }

    /**
     * Una hora sin lecturas es un hueco, no una hora de cero vatios: con 0 la
     * gráfica dibujaría un desplome que nunca pasó.
     */
    #[Test]
    public function las_horas_sin_lecturas_quedan_en_blanco(): void
    {
        $panel = $this->elemento(HardwareEnergy::ROLE_GENERATOR);
        $this->lectura($panel, Carbon::now('UTC')->startOfHour()->subHours(3)->addMinutes(5), 100.0);

        $datos = $this->datos(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data'];

        $this->assertContains(null, $datos);
        $this->assertNotContains(0.0, $datos);
    }

    #[Test]
    public function suma_todos_los_consumos_del_aparato(): void
    {
        // Un medidor con tres pinzas enseña el total de las tres.
        $uno = $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 0);
        $dos = $this->elemento(HardwareEnergy::ROLE_LOAD, canal: 1);
        $hora = Carbon::now('UTC')->startOfHour()->subHours(2);

        $this->lectura($uno, $hora->copy()->addMinutes(5), 10.0);
        $this->lectura($dos, $hora->copy()->addMinutes(5), 30.0);

        $valores = array_values(array_filter($this->datos(HardwareEnergy::ROLE_LOAD)['datasets'][0]['data']));

        $this->assertSame([20.0], $valores, 'Media de los dos canales en esa hora.');
    }

    #[Test]
    public function las_lecturas_sospechosas_no_cuentan(): void
    {
        $panel = $this->elemento(HardwareEnergy::ROLE_GENERATOR);
        $hora = Carbon::now('UTC')->startOfHour()->subHours(2);

        $this->lectura($panel, $hora->copy()->addMinutes(5), 100.0);
        $this->lectura($panel, $hora->copy()->addMinutes(6), 9000.0, sospechosa: true);

        $valores = array_values(array_filter($this->datos(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data']));

        $this->assertSame([100.0], $valores);
    }

    /**
     * En la batería la potencia va con signo: positiva cargando, negativa
     * descargando. Es lo que hace útil su gráfica.
     */
    #[Test]
    public function la_bateria_conserva_el_signo(): void
    {
        $banco = $this->elemento(HardwareEnergy::ROLE_BATTERY);
        $this->lectura($banco, Carbon::now('UTC')->startOfHour()->subHour()->addMinutes(5), -40.0);

        $valores = array_values(array_filter(
            $this->datos(HardwareEnergy::ROLE_BATTERY)['datasets'][0]['data'],
            static fn ($v) => $v !== null,
        ));

        $this->assertSame([-40.0], $valores);
    }

    #[Test]
    public function lo_de_otro_aparato_no_se_mezcla(): void
    {
        $mio = $this->elemento(HardwareEnergy::ROLE_GENERATOR);

        $otro = HardwareDevice::create(['user_id' => $this->aparato->user_id, 'name' => 'Otro']);
        $suyo = HardwareEnergy::create([
            'hardware_device_id' => $otro->id,
            'hardware_device_monitorized_id' => $otro->id,
            'role' => HardwareEnergy::ROLE_GENERATOR,
            'sensor_position' => 0,
        ]);

        $hora = Carbon::now('UTC')->startOfHour()->subHour();
        $this->lectura($mio, $hora->copy()->addMinutes(5), 100.0);

        HardwareEnergyReading::create([
            'hardware_device_id' => $otro->id,
            'hardware_energy_id' => $suyo->id,
            'power' => 5000.0,
            'created_at' => $hora->copy()->addMinutes(5),
            'updated_at' => $hora->copy()->addMinutes(5),
        ]);

        $valores = array_values(array_filter($this->datos(HardwareEnergy::ROLE_GENERATOR)['datasets'][0]['data']));

        $this->assertSame([100.0], $valores);
    }

    #[Test]
    public function cada_papel_tiene_su_titulo(): void
    {
        $grafica = new EnergyRoleTrendChart;

        foreach ([
            HardwareEnergy::ROLE_GENERATOR => 'Generador',
            HardwareEnergy::ROLE_BATTERY => 'Batería',
            HardwareEnergy::ROLE_LOAD => 'Consumo',
        ] as $papel => $esperado) {
            $grafica->papel = $papel;

            $this->assertStringStartsWith($esperado, $grafica->getHeading());
            $this->assertNotNull($grafica->getDescription());
        }
    }
}
