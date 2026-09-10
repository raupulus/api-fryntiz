<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages\ListKeyboards;
use App\Filament\Admin\Resources\KeyCounter\Mice\Pages\ListMice;
use App\Models\Hardware\HardwareDevice;
use App\Models\KeyCounter\Keyboard;
use App\Models\KeyCounter\Mouse;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las tablas de KeyCounter enseñan nombres, no números.
 *
 * La columna «Dispositivo» pintaba `hardware_device_id` en crudo —un id— y la
 * columna «Día», el valor de `weekday`, también en crudo. Y encima el filtro de
 * día usaba la convención contraria a la de los datos: decía que el 0 era
 * domingo cuando el cliente manda 0 = lunes.
 */
class KeyCounterTablesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HardwareDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->user = User::factory()->create(['role_id' => 1, 'is_active' => true]);

        $this->actingAs($this->user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->device = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'thinkpad-t480',
            'name_friendly' => 'Thinkpad de la mesa',
        ]);
    }

    #[Test]
    public function the_keyboard_table_shows_the_name_and_the_day_in_spanish(): void
    {
        Keyboard::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $this->device->id,
            'start_at' => '2026-09-07 10:00:00',
            'end_at' => '2026-09-07 10:05:00',
            'duration' => 300,
            'pulsations' => 1200,
            'pulsations_special_keys' => 40,
            'pulsation_average' => 4.0,
            'score' => 80,
            // 2026-09-07 es lunes, y el cliente manda 0 para el lunes.
            'weekday' => 0,
        ]);

        Livewire::test(ListKeyboards::class)
            ->assertCanSeeTableRecords(Keyboard::all())
            ->assertSee('Thinkpad de la mesa')
            // «Domingo» también aparece, pero en las opciones del filtro de
            // día, así que no sirve de contraprueba.
            ->assertSee('Lunes');
    }

    #[Test]
    public function the_mouse_table_shows_the_name_and_the_day_in_spanish(): void
    {
        Mouse::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $this->device->id,
            'start_at' => '2026-09-13 10:00:00',
            'end_at' => '2026-09-13 10:05:00',
            'duration' => 300,
            'clicks_left' => 200,
            'clicks_right' => 40,
            'clicks_middle' => 10,
            'total_clicks' => 250,
            'clicks_average' => 0.8,
            // 2026-09-13 es domingo: 6 con esta convención.
            'weekday' => 6,
        ]);

        Livewire::test(ListMice::class)
            ->assertSee('Thinkpad de la mesa')
            ->assertSee('Domingo');
    }

    /**
     * La columna «Dispositivo» pasó de pintar `hardware_device_id` —una columna
     * de la propia fila— a resolver la relación. Eso es exactamente cómo se
     * mete un N+1 en una tabla paginada sin darse cuenta: el número de
     * consultas no puede crecer con el número de filas.
     */
    #[Test]
    public function the_device_column_does_not_add_a_query_per_row(): void
    {
        $devices = collect(range(1, 5))->map(fn (int $i) => HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => "cacharro-{$i}",
            'name_friendly' => "Cacharro {$i}",
        ]));

        $createStreaks = function (int $howMany) use ($devices): void {
            foreach (range(1, $howMany) as $i) {
                Keyboard::create([
                    'user_id' => $this->user->id,
                    'hardware_device_id' => $devices[$i % 5]->id,
                    'start_at' => now()->subMinutes($i),
                    'end_at' => now()->subMinutes($i),
                    'duration' => 60,
                    'pulsations' => 100,
                    'pulsations_special_keys' => 1,
                    'pulsation_average' => 1.0,
                    'score' => 5,
                    'weekday' => 0,
                ]);
            }
        };

        $queriesWith = function (int $rows) use ($createStreaks): int {
            Keyboard::query()->delete();
            $createStreaks($rows);

            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::test(ListKeyboards::class)->assertSuccessful();

            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        $withFive = $queriesWith(5);
        $withThirty = $queriesWith(30);

        $this->assertSame(
            $withFive,
            $withThirty,
            "Con 5 filas hace {$withFive} consultas y con 30 hace {$withThirty}: "
            .'la columna «Dispositivo» está resolviendo la relación fila a fila.',
        );
    }

    /**
     * Un dispositivo sin nombre amigable cae en `name`, que es lo que hace
     * `display_name`. Sin esto la columna saldría vacía.
     */
    #[Test]
    public function without_a_friendly_name_it_shows_the_plain_name(): void
    {
        $bareDevice = HardwareDevice::create([
            'user_id' => $this->user->id,
            'name' => 'raspberry-pico-w',
        ]);

        Keyboard::create([
            'user_id' => $this->user->id,
            'hardware_device_id' => $bareDevice->id,
            'start_at' => '2026-09-07 10:00:00',
            'end_at' => '2026-09-07 10:05:00',
            'duration' => 300,
            'pulsations' => 10,
            'pulsations_special_keys' => 1,
            'pulsation_average' => 1.0,
            'score' => 5,
            'weekday' => 0,
        ]);

        Livewire::test(ListKeyboards::class)->assertSee('raspberry-pico-w');
    }
}
