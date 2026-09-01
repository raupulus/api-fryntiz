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
    public function cada_uno_se_ve_y_se_edita_a_si_mismo(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($this->policy->view($user, $user));
        $this->assertTrue($this->policy->update($user, $user));
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
}
