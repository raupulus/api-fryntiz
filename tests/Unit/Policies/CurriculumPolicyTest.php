<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\CV\Curriculum;
use App\Models\User;
use App\Policies\CurriculumPolicy;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ⚠️ Roles 2 y 3, nunca SuperAdmin: `Gate::before` lo dejaría pasar sin
 * ejecutar la policy (AGENTS.md §12).
 */
class CurriculumPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CurriculumPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->policy = new CurriculumPolicy;
    }

    private function makeUser(UserRoleEnum $role = UserRoleEnum::User): User
    {
        return User::factory()->create(['role_id' => $role->value]);
    }

    private function makeCurriculum(User $owner): Curriculum
    {
        return Curriculum::create([
            'user_id' => $owner->id,
            'title' => 'CV de pruebas',
            'slug' => 'cv-'.uniqid(),
            'presentation' => 'Una presentación.',
        ]);
    }

    #[Test]
    public function el_dueno_ve_edita_y_borra_su_curriculum(): void
    {
        $user = $this->makeUser();
        $cv = $this->makeCurriculum($user);

        $this->assertTrue($this->policy->view($user, $cv));
        $this->assertTrue($this->policy->update($user, $cv));
        $this->assertTrue($this->policy->delete($user, $cv));
    }

    #[Test]
    public function un_usuario_normal_no_toca_el_curriculum_de_otro(): void
    {
        $ajeno = $this->makeCurriculum($this->makeUser());
        $mirón = $this->makeUser();

        $this->assertFalse($this->policy->view($mirón, $ajeno));
        $this->assertFalse($this->policy->update($mirón, $ajeno));
        $this->assertFalse($this->policy->delete($mirón, $ajeno));
    }

    #[Test]
    public function el_admin_alcanza_cualquier_curriculum(): void
    {
        $ajeno = $this->makeCurriculum($this->makeUser());

        $this->assertTrue($this->policy->view($this->makeUser(UserRoleEnum::Admin), $ajeno));
        $this->assertTrue($this->policy->update($this->makeUser(UserRoleEnum::Admin), $ajeno));
    }

    #[Test]
    public function cualquiera_puede_listar(): void
    {
        // El filtrado de qué currículos se ven es de la consulta, no de aquí.
        $this->assertTrue($this->policy->viewAny($this->makeUser()));
    }
}
