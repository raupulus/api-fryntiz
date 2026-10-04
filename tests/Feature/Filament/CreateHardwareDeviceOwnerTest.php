<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\CreateHardwareDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Al crear un dispositivo desde el panel, el propietario por defecto es quien
 * lo crea: sin `user_id` el botón "Emitir token" no aparece.
 */
class CreateHardwareDeviceOwnerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_formulario_de_creacion_propone_al_usuario_autenticado_como_propietario(): void
    {
        foreach ([[1, 'superadmin'], [2, 'admin'], [3, 'user'], [4, 'editor']] as [$id, $name]) {
            DB::table('user_roles')->insert([
                'id' => $id, 'name' => $name, 'display_name' => $name, 'slug' => $name,
                'description' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $admin = User::factory()->create();
        $admin->forceFill(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true])->save();

        $this->actingAs($admin->fresh());

        Livewire::test(CreateHardwareDevice::class)
            ->assertFormSet(['user_id' => $admin->id]);
    }
}
