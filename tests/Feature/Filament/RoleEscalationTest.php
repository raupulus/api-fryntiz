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

    private function actAsRole(UserRoleEnum $role): User
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
    public function an_admin_cannot_open_their_own_edit_page(): void
    {
        $admin = $this->actAsRole(UserRoleEnum::Admin);

        $this->get(UserResource::getUrl('edit', ['record' => $admin], panel: 'admin'))
            ->assertForbidden();
    }

    /**
     * Lo que el usuario reportó tras el despliegue: «con un admin puedo editar
     * el rol de un usuario administrador». La página no debe ni abrirse.
     */
    #[Test]
    public function an_admin_cannot_open_a_superadmins_edit_page(): void
    {
        $this->actAsRole(UserRoleEnum::Admin);

        $superadmin = User::factory()->create([
            'role_id' => UserRoleEnum::SuperAdmin->value,
        ]);

        $this->get(UserResource::getUrl('edit', ['record' => $superadmin], panel: 'admin'))
            ->assertForbidden();
    }

    /**
     * Y si llegara a montarse el formulario, el `Select` de rol está
     * deshabilitado y no se persiste: la interfaz no ofrece lo que la policy
     * va a rechazar.
     */
    #[Test]
    public function the_role_select_is_locked_on_a_superadmin(): void
    {
        $this->actAsRole(UserRoleEnum::Admin);

        $superadmin = User::factory()->create([
            'role_id' => UserRoleEnum::SuperAdmin->value,
        ]);

        $this->assertTrue(UserResourceProbe::untouchable($superadmin));

        // Sobre otro `Admin` sí se puede: repartir el mismo nivel no es escalar.
        $anotherAdmin = User::factory()->create(['role_id' => UserRoleEnum::Admin->value]);

        $this->assertFalse(UserResourceProbe::untouchable($anotherAdmin));
    }

    #[Test]
    public function an_admin_cannot_promote_themselves_to_superadmin(): void
    {
        $admin = $this->actAsRole(UserRoleEnum::Admin);

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
    public function an_admin_cannot_create_a_superadmin(): void
    {
        $this->actAsRole(UserRoleEnum::Admin);

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
    public function an_admin_can_create_another_admin(): void
    {
        // Cerrar la escalada no puede llevarse por delante el trabajo normal
        // del panel: repartir su mismo nivel no es escalar.
        $this->actAsRole(UserRoleEnum::Admin);

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
    public function an_admin_can_edit_another_user(): void
    {
        $this->actAsRole(UserRoleEnum::Admin);

        $anotherUser = User::factory()->create(['role_id' => UserRoleEnum::User->value]);

        Livewire::test(EditUser::class, ['record' => $anotherUser->getKey()])
            ->fillForm(['name' => 'Nombre cambiado'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nombre cambiado', $anotherUser->fresh()->name);
    }

    #[Test]
    public function a_superadmin_can_still_assign_any_role(): void
    {
        $this->actAsRole(UserRoleEnum::SuperAdmin);

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

/**
 * `UserResource::isUntouchable()` es `protected` porque es un detalle del
 * formulario, no una API. Esto lo alcanza sin abrirlo al resto del proyecto.
 */
class UserResourceProbe extends UserResource
{
    public static function untouchable(?User $record): bool
    {
        return static::isUntouchable($record);
    }
}
