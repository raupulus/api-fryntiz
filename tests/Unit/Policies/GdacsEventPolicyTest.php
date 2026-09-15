<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Gdacs\GdacsEvent;
use App\Models\User;
use App\Policies\GdacsEventPolicy;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdacsEventPolicyTest extends TestCase
{
    use RefreshDatabase;

    private GdacsEventPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->policy = new GdacsEventPolicy;
    }

    private function makeUser(UserRoleEnum $role): User
    {
        return User::factory()->create(['role_id' => $role->value]);
    }

    #[Test]
    public function only_an_admin_lists_and_views_events(): void
    {
        $admin = $this->makeUser(UserRoleEnum::Admin);
        $editor = $this->makeUser(UserRoleEnum::Editor);
        $user = $this->makeUser(UserRoleEnum::User);
        $event = GdacsEvent::factory()->create();

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->view($admin, $event));

        $this->assertFalse($this->policy->viewAny($editor));
        $this->assertFalse($this->policy->view($editor, $event));

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $event));
    }

    /**
     * Solo `gdacs:sync` escribe aquí. Ni siquiera un administrador crea,
     * edita o borra un evento a mano desde el panel — editarlo a mano
     * desincroniza la copia local de lo que GDACS dice de verdad.
     */
    #[Test]
    public function no_one_creates_edits_or_deletes_an_event_by_hand(): void
    {
        $admin = $this->makeUser(UserRoleEnum::Admin);
        $event = GdacsEvent::factory()->create();

        $this->assertFalse($this->policy->create($admin));
        $this->assertFalse($this->policy->update($admin, $event));
        $this->assertFalse($this->policy->delete($admin, $event));
        $this->assertFalse($this->policy->deleteAny($admin));
    }
}
