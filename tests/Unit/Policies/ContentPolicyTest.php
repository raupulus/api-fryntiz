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
    public function un_usuario_normal_no_entra_al_listado_de_contenidos(): void
    {
        $this->assertFalse($this->policy->viewAny($this->makeUser(UserRoleEnum::User)));
    }

    #[Test]
    public function admin_y_editor_entran_al_listado(): void
    {
        $this->assertTrue($this->policy->viewAny($this->makeUser(UserRoleEnum::Admin)));
        $this->assertTrue($this->policy->viewAny($this->makeUser(UserRoleEnum::Editor)));
    }

    #[Test]
    public function un_usuario_normal_no_crea_contenidos(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser(UserRoleEnum::User)));
    }

    // ─── view / update ───

    #[Test]
    public function el_autor_alcanza_su_propio_contenido(): void
    {
        $autor = $this->makeUser(UserRoleEnum::User);
        $content = $this->makeContent($autor);

        $this->assertTrue($this->policy->view($autor, $content));
        $this->assertTrue($this->policy->update($autor, $content));
    }

    #[Test]
    public function un_usuario_normal_no_alcanza_el_contenido_de_otro(): void
    {
        $ajeno = $this->makeContent($this->makeUser(UserRoleEnum::User));
        $mirón = $this->makeUser(UserRoleEnum::User);

        $this->assertFalse($this->policy->view($mirón, $ajeno));
        $this->assertFalse($this->policy->update($mirón, $ajeno));
    }

    #[Test]
    public function el_admin_alcanza_cualquier_contenido(): void
    {
        $ajeno = $this->makeContent($this->makeUser(UserRoleEnum::User));

        $this->assertTrue($this->policy->view($this->makeUser(UserRoleEnum::Admin), $ajeno));
    }

    #[Test]
    public function un_editor_solo_alcanza_las_plataformas_que_tiene_asignadas(): void
    {
        // Es el eje que da sentido a esta policy: poder tener a alguien que
        // escriba en una web y no en las otras.
        $suya = Platform::factory()->create();
        $ajena = Platform::factory()->create();

        $editor = $this->makeUser(UserRoleEnum::Editor);
        $editor->platforms()->attach($suya->id);

        $propio = $this->makeContent(null, $suya);
        $deOtraWeb = $this->makeContent(null, $ajena);

        $this->assertTrue($this->policy->update($editor, $propio));
        $this->assertFalse($this->policy->update($editor, $deOtraWeb));
    }

    #[Test]
    public function un_contenido_sin_plataforma_es_de_administracion_general(): void
    {
        $editor = $this->makeUser(UserRoleEnum::Editor);
        $editor->platforms()->attach(Platform::factory()->create()->id);

        $general = $this->makeContent(null, null);

        $this->assertFalse($this->policy->update($editor, $general));
        $this->assertTrue($this->policy->update($this->makeUser(UserRoleEnum::Admin), $general));
    }

    // ─── delete ───

    #[Test]
    public function un_editor_no_borra_el_contenido_de_otro_aunque_alcance_la_plataforma(): void
    {
        // Alcanzar para editar no es alcanzar para borrar: sólo se borra lo
        // propio, salvo que seas admin.
        $platform = Platform::factory()->create();

        $editor = $this->makeUser(UserRoleEnum::Editor);
        $editor->platforms()->attach($platform->id);

        $deOtro = $this->makeContent($this->makeUser(UserRoleEnum::User), $platform);

        $this->assertTrue($this->policy->update($editor, $deOtro));
        $this->assertFalse($this->policy->delete($editor, $deOtro));
    }

    #[Test]
    public function el_admin_borra_cualquier_contenido(): void
    {
        $deOtro = $this->makeContent($this->makeUser(UserRoleEnum::User));

        $this->assertTrue($this->policy->delete($this->makeUser(UserRoleEnum::Admin), $deOtro));
    }

    #[Test]
    public function solo_el_superadmin_borra_definitivamente(): void
    {
        $content = $this->makeContent();

        $this->assertFalse($this->policy->forceDelete($this->makeUser(UserRoleEnum::Admin), $content));
        $this->assertFalse($this->policy->forceDelete($this->makeUser(UserRoleEnum::Editor), $content));
    }
}
