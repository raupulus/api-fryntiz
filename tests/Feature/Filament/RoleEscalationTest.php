<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\UserResource;
use App\Filament\Admin\Resources\UserResource\Pages\CreateUser;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Nadie se sube de rol desde el panel.
 *
 * Esto reproduce el ataque de la auditoría **AR-P01**, que estaba abierto y se
 * comprobó ejecutándolo: un usuario con rol `Admin` abría
 * `/admin/users/{su_id}/edit`, cambiaba `role_id` a `SuperAdmin` y guardaba.
 * A partir de ahí tenía el bypass total de `Gate::before`, o sea acceso a todo
 * sin pasar por ninguna de las 16 policies.
 *
 * Había dos caminos y los dos se prueban aquí:
 *
 *  1. **Editarse a uno mismo** y cambiarse el rol.
 *  2. **Crear un usuario nuevo** con rol `SuperAdmin` y una contraseña elegida
 *     por quien lo crea, para entrar después con esa cuenta.
 */
class RoleEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function actuarComo(UserRoleEnum $role): User
    {
        $user = User::factory()->create([
            'role_id' => $role->value,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    #[Test]
    public function un_admin_no_puede_abrir_su_propia_edicion(): void
    {
        $admin = $this->actuarComo(UserRoleEnum::Admin);

        $this->get(UserResource::getUrl('edit', ['record' => $admin], panel: 'admin'))
            ->assertForbidden();
    }

    #[Test]
    public function un_admin_no_puede_ponerse_superadmin(): void
    {
        $admin = $this->actuarComo(UserRoleEnum::Admin);

        try {
            Livewire::test(EditUser::class, ['record' => $admin->getKey()])
                ->fillForm(['role_id' => UserRoleEnum::SuperAdmin->value])
                ->call('save');
        } catch (\Throwable) {
            // Que la página ni se monte es el resultado bueno. Lo que importa
            // es la comprobación de abajo.
        }

        $admin->refresh();

        $this->assertSame(UserRoleEnum::Admin->value, (int) $admin->role_id);
        $this->assertFalse($admin->isSuperAdmin());
    }

    #[Test]
    public function un_admin_no_puede_crear_un_superadmin(): void
    {
        $this->actuarComo(UserRoleEnum::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Colado',
                'email' => 'colado@raupulus.dev',
                'password' => 'UnaContrasenaLarga123',
                'role_id' => UserRoleEnum::SuperAdmin->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['role_id']);

        $this->assertDatabaseMissing('users', [
            'email' => 'colado@raupulus.dev',
            'role_id' => UserRoleEnum::SuperAdmin->value,
        ]);
    }

    #[Test]
    public function un_admin_si_puede_crear_un_admin(): void
    {
        // Cerrar la escalada no puede llevarse por delante el trabajo normal
        // del panel: repartir su mismo nivel no es escalar.
        $this->actuarComo(UserRoleEnum::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Compañero',
                'email' => 'companero@raupulus.dev',
                'password' => 'UnaContrasenaLarga123',
                'role_id' => UserRoleEnum::Admin->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'companero@raupulus.dev',
            'role_id' => UserRoleEnum::Admin->value,
        ]);
    }

    #[Test]
    public function un_admin_si_puede_editar_a_otro_usuario(): void
    {
        $this->actuarComo(UserRoleEnum::Admin);

        $otro = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        Livewire::test(EditUser::class, ['record' => $otro->getKey()])
            ->fillForm(['name' => 'Nombre cambiado'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nombre cambiado', $otro->fresh()->name);
    }

    #[Test]
    public function un_superadmin_sigue_pudiendo_repartir_cualquier_rol(): void
    {
        $this->actuarComo(UserRoleEnum::SuperAdmin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Relevo',
                'email' => 'relevo@raupulus.dev',
                'password' => 'UnaContrasenaLarga123',
                'role_id' => UserRoleEnum::SuperAdmin->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'relevo@raupulus.dev',
            'role_id' => UserRoleEnum::SuperAdmin->value,
        ]);
    }
}
