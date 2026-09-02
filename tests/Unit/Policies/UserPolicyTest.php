<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ⚠️ Se prueba la clase directamente y con roles 2 y 3. Con `Gate::before` un
 * SuperAdmin ni siquiera entraría aquí (AGENTS.md §12) — salvo donde el
 * SuperAdmin es el SUJETO de la comprobación, que es el caso de `delete()`.
 */
class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->policy = new UserPolicy;
    }

    private function makeUser(UserRoleEnum $role = UserRoleEnum::User): User
    {
        return User::factory()->create(['role_id' => $role->value]);
    }

    #[Test]
    public function un_usuario_normal_no_lista_usuarios(): void
    {
        $this->assertFalse($this->policy->viewAny($this->makeUser()));
        $this->assertTrue($this->policy->viewAny($this->makeUser(UserRoleEnum::Admin)));
    }

    #[Test]
    public function cada_uno_se_ve_a_si_mismo(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->policy->view($user, $user));
    }

    #[Test]
    public function nadie_se_edita_a_si_mismo_desde_el_recurso_de_usuarios(): void
    {
        // AR-P01. `update()` devolvía true para el propio registro, y el
        // formulario del recurso incluye `role_id`: con las dos cosas juntas un
        // Admin entraba en /admin/users/{su_id}/edit, se ponía SuperAdmin y se
        // llevaba el bypass total de Gate::before.
        //
        // El autoservicio va por App\Filament\{Admin\Pages\Profile,
        // Tenant\Pages\EditProfile}, que no exponen ni el rol ni is_active.
        $user = $this->makeUser();
        $admin = $this->makeUser(UserRoleEnum::Admin);

        $this->assertFalse($this->policy->update($user, $user));
        $this->assertFalse($this->policy->update($admin, $admin));
    }

    #[Test]
    public function un_admin_sigue_editando_a_otros_usuarios(): void
    {
        // Cerrar el autoservicio no puede llevarse por delante el trabajo real
        // del panel.
        $admin = $this->makeUser(UserRoleEnum::Admin);

        $this->assertTrue($this->policy->update($admin, $this->makeUser()));
        $this->assertTrue($this->policy->update($admin, $this->makeUser(UserRoleEnum::Editor)));
    }

    #[Test]
    public function un_usuario_normal_no_ve_ni_edita_a_otro(): void
    {
        $user = $this->makeUser();
        $otro = $this->makeUser();

        $this->assertFalse($this->policy->view($user, $otro));
        $this->assertFalse($this->policy->update($user, $otro));
    }

    #[Test]
    public function un_admin_no_puede_editar_a_un_superadmin(): void
    {
        // El escalado obvio: si un admin pudiera editar al superadmin, podría
        // cambiarle la contraseña y quedarse con la cuenta que manda.
        $admin = $this->makeUser(UserRoleEnum::Admin);
        $superadmin = $this->makeUser(UserRoleEnum::SuperAdmin);

        $this->assertFalse($this->policy->update($admin, $superadmin));
        $this->assertFalse($this->policy->delete($admin, $superadmin));
        $this->assertFalse($this->policy->forceDelete($admin, $superadmin));
    }

    #[Test]
    public function un_admin_no_borra_usuarios(): void
    {
        // Borrar cuentas es cosa del superadmin.
        $admin = $this->makeUser(UserRoleEnum::Admin);

        $this->assertFalse($this->policy->delete($admin, $this->makeUser()));
    }

    #[Test]
    public function un_superadmin_no_se_borra_a_si_mismo(): void
    {
        // Aquí el SuperAdmin es el sujeto de la comprobación, así que el test
        // sí tiene sentido con ese rol: se comprueba que no puede dejar el
        // sistema sin ninguna cuenta que mande.
        $superadmin = $this->makeUser(UserRoleEnum::SuperAdmin);

        $this->assertFalse($this->policy->delete($superadmin, $superadmin));
        $this->assertFalse($this->policy->forceDelete($superadmin, $superadmin));
    }

    #[Test]
    public function un_superadmin_borra_a_otros(): void
    {
        $superadmin = $this->makeUser(UserRoleEnum::SuperAdmin);

        $this->assertTrue($this->policy->delete($superadmin, $this->makeUser()));
    }

    #[Test]
    public function un_usuario_normal_no_crea_usuarios(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser()));
        $this->assertTrue($this->policy->create($this->makeUser(UserRoleEnum::Admin)));
    }

    #[Test]
    public function nadie_reparte_un_rol_por_encima_del_suyo(): void
    {
        // La otra mitad de AR-P01: aunque no pueda editarse a sí mismo, un
        // Admin podía dar de alta un SuperAdmin nuevo con una contraseña
        // elegida por él y entrar con esa cuenta. El criterio está en el enum y
        // el formulario lo aplica por partida doble (opciones + regla `in`).
        $admin = $this->makeUser(UserRoleEnum::Admin);
        $superadmin = $this->makeUser(UserRoleEnum::SuperAdmin);

        $this->assertFalse($admin->canAssignRole(UserRoleEnum::SuperAdmin));
        $this->assertTrue($admin->canAssignRole(UserRoleEnum::Admin));
        $this->assertTrue($admin->canAssignRole(UserRoleEnum::Editor));
        $this->assertTrue($admin->canAssignRole(UserRoleEnum::User));

        $this->assertTrue($superadmin->canAssignRole(UserRoleEnum::SuperAdmin));

        $this->assertSame([2, 3, 4], $admin->assignableRoleIds());
        $this->assertSame([1, 2, 3, 4], $superadmin->assignableRoleIds());
    }

    #[Test]
    public function un_editor_y_un_usuario_no_reparten_ningun_rol(): void
    {
        $this->assertSame([], $this->makeUser(UserRoleEnum::Editor)->assignableRoleIds());
        $this->assertSame([], $this->makeUser(UserRoleEnum::User)->assignableRoleIds());
    }

    #[Test]
    public function un_rol_desconocido_no_reparte_nada(): void
    {
        // Falla cerrado: un `role_id` fuera del catálogo no debe abrir el
        // formulario entero.
        $user = $this->makeUser();
        $user->role_id = 99;

        $this->assertNull($user->roleEnum());
        $this->assertSame([], $user->assignableRoleIds());
        $this->assertFalse($user->canAssignRole(UserRoleEnum::User));
    }
}
