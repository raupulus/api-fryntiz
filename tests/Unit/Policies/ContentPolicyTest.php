<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Platform;
use App\Models\User;
use App\Policies\ContentPolicy;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ⚠️ Se prueba con roles Admin (2), User (3) y Editor (4), NUNCA con
 * SuperAdmin: `Gate::before` deja pasar al SuperAdmin sin llegar a la policy,
 * así que un test con SuperAdmin pasaría en verde sin ejecutar nada de lo que
 * se quiere comprobar (AGENTS.md §12).
 *
 * Aquí se prueba la clase directamente, sin pasar por el Gate, que es la otra
 * forma de que `Gate::before` no enmascare el resultado.
 */
class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ContentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();

        $this->policy = new ContentPolicy;
    }

    private function makeUser(UserRoleEnum $role): User
    {
        return User::factory()->create(['role_id' => $role->value]);
    }

    private function makeContent(?User $author = null, ?Platform $platform = null): Content
    {
        return Content::factory()->create([
            'author_id' => $author?->id,
            'platform_id' => $platform?->id,
        ]);
    }

    // ─── viewAny / create ───

    #[Test]
    public function a_regular_user_does_not_enter_the_content_listing(): void
    {
        $this->assertFalse($this->policy->viewAny($this->makeUser(UserRoleEnum::User)));
    }

    #[Test]
    public function admin_and_editor_enter_the_listing(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser(UserRoleEnum::Admin)));
        $this->assertTrue($this->policy->viewAny($this->makeUser(UserRoleEnum::Editor)));
    }

    #[Test]
    public function a_regular_user_does_not_create_content(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(UserRoleEnum::User)));
    }

    // ─── view / update ───

    #[Test]
    public function the_author_can_reach_their_own_content(): void
    {
        $author = $this->makeUser(UserRoleEnum::User);
        $content = $this->makeContent($author);

        $this->assertTrue($this->policy->view($author, $content));
        $this->assertTrue($this->policy->update($author, $content));
    }

    #[Test]
    public function a_regular_user_cannot_reach_another_users_content(): void
    {
        $othersContent = $this->makeContent($this->makeUser(UserRoleEnum::User));
        $viewer = $this->makeUser(UserRoleEnum::User);

        $this->assertFalse($this->policy->view($viewer, $othersContent));
        $this->assertFalse($this->policy->update($viewer, $othersContent));
    }

    #[Test]
    public function the_admin_can_reach_any_content(): void
    {
        $othersContent = $this->makeContent($this->makeUser(UserRoleEnum::User));

        $this->assertTrue($this->policy->view($this->makeUser(UserRoleEnum::Admin), $othersContent));
    }

    #[Test]
    public function an_editor_only_reaches_the_platforms_they_have_assigned(): void
    {
        // Es el eje que da sentido a esta policy: poder tener a alguien que
        // escriba en una web y no en las otras.
        $ownPlatform = Platform::factory()->create();
        $otherPlatform = Platform::factory()->create();

        $editor = $this->makeUser(UserRoleEnum::Editor);
        $editor->platforms()->attach($ownPlatform->id);

        $ownContent = $this->makeContent(null, $ownPlatform);
        $otherSiteContent = $this->makeContent(null, $otherPlatform);

        $this->assertTrue($this->policy->update($editor, $ownContent));
        $this->assertFalse($this->policy->update($editor, $otherSiteContent));
    }

    #[Test]
    public function content_without_a_platform_belongs_to_general_administration(): void
    {
        $editor = $this->makeUser(UserRoleEnum::Editor);
        $editor->platforms()->attach(Platform::factory()->create()->id);

        $generalContent = $this->makeContent(null, null);

        $this->assertFalse($this->policy->update($editor, $generalContent));
        $this->assertTrue($this->policy->update($this->makeUser(UserRoleEnum::Admin), $generalContent));
    }

    // ─── delete ───

    #[Test]
    public function an_editor_does_not_delete_another_users_content_even_when_reaching_the_platform(): void
    {
        // Alcanzar para editar no es alcanzar para borrar: sólo se borra lo
        // propio, salvo que seas admin.
        $platform = Platform::factory()->create();

        $editor = $this->makeUser(UserRoleEnum::Editor);
        $editor->platforms()->attach($platform->id);

        $othersContent = $this->makeContent($this->makeUser(UserRoleEnum::User), $platform);

        $this->assertTrue($this->policy->update($editor, $othersContent));
        $this->assertFalse($this->policy->delete($editor, $othersContent));
    }

    #[Test]
    public function the_admin_deletes_any_content(): void
    {
        $othersContent = $this->makeContent($this->makeUser(UserRoleEnum::User));

        $this->assertTrue($this->policy->delete($this->makeUser(UserRoleEnum::Admin), $othersContent));
    }

    #[Test]
    public function only_the_superadmin_can_force_delete(): void
    {
        $content = $this->makeContent();

        $this->assertFalse($this->policy->forceDelete($this->makeUser(UserRoleEnum::Admin), $content));
        $this->assertFalse($this->policy->forceDelete($this->makeUser(UserRoleEnum::Editor), $content));
    }
}
