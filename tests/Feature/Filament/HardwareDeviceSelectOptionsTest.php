<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\Pages\EditAirFlightAirPlane;
use App\Filament\Admin\Resources\KeyCounter\Keyboards\Pages\ListKeyboards;
use App\Filament\Admin\Resources\KeyCounter\Mice\Pages\ListMice;
use App\Models\AirFlight\AirFlightAirPlane;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `Select::make('hardware_device_id')->relationship('hardwareDevice', 'name_friendly')`
 * reventaba con un `TypeError` en cuanto existía un `HardwareDevice` con
 * `name_friendly` a NULL (columna nullable a propósito): Filament pasa el
 * título de cada opción tal cual a `isOptionDisabled()`, que exige
 * `string|Htmlable`, nunca `null`.
 *
 * El fix usa `getOptionLabelFromRecordUsing()` con el accessor `display_name`
 * del modelo, que ya resuelve el fallback (`name_friendly` → `name` →
 * `"Estación #{id}"`) y siempre devuelve un string.
 */
class HardwareDeviceSelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La tabla `users` tiene `role_id` con un valor por defecto (3 = User) a
        // nivel de columna: hace falta la fila incluso para el `User::factory()`
        // sin especificar rol, no sólo para el SuperAdmin del test.
        $roles = [
            ['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Administrador Principal'],
            ['id' => 2, 'name' => 'admin', 'display_name' => 'Admin', 'slug' => 'admin', 'description' => 'Administradores'],
            ['id' => 3, 'name' => 'user', 'display_name' => 'Usuario', 'slug' => 'usuario', 'description' => 'Usuario normal'],
            ['id' => 4, 'name' => 'editor', 'display_name' => 'Editor', 'slug' => 'editor', 'description' => 'Edita contenido sólo en las plataformas que tenga asignadas'],
        ];

        foreach ($roles as $role) {
            DB::table('user_roles')->insert($role + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role_id' => UserRoleEnum::SuperAdmin->value, 'is_active' => true])->save();

        return $user->fresh();
    }

    #[Test]
    public function keyboards_list_loads_with_a_hardware_device_without_a_friendly_name(): void
    {
        HardwareDevice::create(['name' => null, 'name_friendly' => null]);

        Livewire::actingAs($this->admin())
            ->test(ListKeyboards::class)
            ->assertOk();
    }

    #[Test]
    public function mice_list_loads_with_a_hardware_device_without_a_friendly_name(): void
    {
        HardwareDevice::create(['name' => null, 'name_friendly' => null]);

        Livewire::actingAs($this->admin())
            ->test(ListMice::class)
            ->assertOk();
    }

    #[Test]
    public function airplane_edit_form_loads_with_a_hardware_device_without_a_friendly_name(): void
    {
        HardwareDevice::create(['name' => null, 'name_friendly' => null]);
        $plane = AirFlightAirPlane::create(['icao' => 'A1B2C3']);

        Livewire::actingAs($this->admin())
            ->test(EditAirFlightAirPlane::class, ['record' => $plane->getKey()])
            ->assertOk();
    }
}
