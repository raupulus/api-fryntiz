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
    public function the_owner_views_edits_and_deletes_their_curriculum(): void
    {
        $user = $this->makeUser();
        $cv = $this->makeCurriculum($user);

        $this->assertTrue($this->policy->view($user, $cv));
        $this->assertTrue($this->policy->update($user, $cv));
        $this->assertTrue($this->policy->delete($user, $cv));
    }

    #[Test]
    public function a_regular_user_does_not_touch_another_users_curriculum(): void
    {
        $othersCurriculum = $this->makeCurriculum($this->makeUser());
        $viewer = $this->makeUser();

        $this->assertFalse($this->policy->view($viewer, $othersCurriculum));
        $this->assertFalse($this->policy->update($viewer, $othersCurriculum));
        $this->assertFalse($this->policy->delete($viewer, $othersCurriculum));
    }

    #[Test]
    public function the_admin_can_reach_any_curriculum(): void
    {
        $othersCurriculum = $this->makeCurriculum($this->makeUser());

        $this->assertTrue($this->policy->view($this->makeUser(UserRoleEnum::Admin), $othersCurriculum));
        $this->assertTrue($this->policy->update($this->makeUser(UserRoleEnum::Admin), $othersCurriculum));
    }

    #[Test]
    public function anyone_can_list(): void
    {
        // El filtrado de qué currículos se ven es de la consulta, no de aquí.
        $this->assertTrue($this->policy->viewAny($this->makeUser()));
    }
}
