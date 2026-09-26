<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentContributor;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use App\Models\User;
use App\Policies\ContentPagePolicy;
use App\Policies\ContentPolicy;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * Matriz de permisos sobre contenidos (B3 y DUDA-2 del plan de contenidos del
 * 2026-09-24): cada perfil contra cada acción, con el resultado que se decidió.
 *
 * Se prueba la clase directamente, sin pasar por el Gate: `Gate::before` deja
 * pasar al SuperAdmin sin llegar a la política, y un test por el Gate pasaría
 * en verde sin ejecutar nada (AGENTS.md §12).
 */
class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private const PROFILES = [
        'superadmin', 'admin', 'editor autor', 'editor colaborador', 'editor sin relación',
        'editor con la plataforma y sin relación', 'colaborador quitado', 'usuario normal',
    ];

    /**
     * Acción => perfiles que pueden. Los que no están, no pueden.
     */
    private const MATRIX = [
        'view' => ['superadmin', 'admin', 'editor autor', 'editor colaborador'],
        'update' => ['superadmin', 'admin', 'editor autor', 'editor colaborador'],
        // Las páginas siguen al contenido (ContentPagePolicy).
        'editar sus páginas' => ['superadmin', 'admin', 'editor autor', 'editor colaborador'],
        'borrar sus páginas' => ['superadmin', 'admin', 'editor autor', 'editor colaborador'],
        'delete' => ['superadmin', 'admin', 'editor autor'],
        'manage' => ['superadmin', 'admin', 'editor autor'],
        'publish' => ['superadmin', 'admin', 'editor autor'],
        'forceDelete' => ['superadmin'],
        // Programar dentro de 2 días: sólo con el control completo.
        'schedule en 2 días' => ['superadmin', 'admin', 'editor autor'],
        // Dentro de 8 días: también un colaborador (mínimo 7).
        'schedule en 8 días' => ['superadmin', 'admin', 'editor autor', 'editor colaborador'],
    ];

    private ContentPolicy $policy;

    private Platform $platform;

    private Content $content;

    private ContentPage $page;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        (new ContentAvailableTypesSeeder)->run();

        $this->policy = new ContentPolicy;
        $this->platform = Platform::factory()->create();

        $make = fn (UserRoleEnum $role): User => User::factory()->create(['role_id' => $role->value, 'is_active' => true]);

        $this->users = [
            'superadmin' => $make(UserRoleEnum::SuperAdmin),
            'admin' => $make(UserRoleEnum::Admin),
            'editor autor' => $make(UserRoleEnum::Editor),
            'editor colaborador' => $make(UserRoleEnum::Editor),
            'editor sin relación' => $make(UserRoleEnum::Editor),
            'editor con la plataforma y sin relación' => $make(UserRoleEnum::Editor),
            'colaborador quitado' => $make(UserRoleEnum::Editor),
            'usuario normal' => $make(UserRoleEnum::User),
        ];

        $this->content = Content::factory()->create([
            'author_id' => $this->users['editor autor']->id,
            'platform_id' => $this->platform->id,
        ]);

        $this->page = ContentPage::query()->create(['content_id' => $this->content->id, 'title' => 'Página', 'slug' => 'pagina', 'order' => 1]);

        $this->users['editor con la plataforma y sin relación']->platforms()->attach($this->platform->id);
        ContentContributor::query()->create(['content_id' => $this->content->id, 'user_id' => $this->users['editor colaborador']->id]);
        ContentContributor::query()->create(['content_id' => $this->content->id, 'user_id' => $this->users['colaborador quitado']->id])->delete();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function cells(): array
    {
        $cells = [];

        foreach (array_keys(self::MATRIX) as $action) {
            foreach (self::PROFILES as $profile) {
                $cells["{$action} · {$profile}"] = [$action, $profile];
            }
        }

        return $cells;
    }

    #[Test]
    #[DataProvider('cells')]
    public function each_profile_gets_what_was_decided_for_each_action(string $action, string $profile): void
    {
        $user = $this->users[$profile];

        $allowed = match ($action) {
            'schedule en 2 días' => $this->policy->schedule($user, $this->content, now()->addDays(2)),
            'schedule en 8 días' => $this->policy->schedule($user, $this->content, now()->addDays(8)),
            'editar sus páginas' => (new ContentPagePolicy($this->policy))->update($user, $this->page),
            'borrar sus páginas' => (new ContentPagePolicy($this->policy))->delete($user, $this->page),
            default => $this->policy->{$action}($user, $this->content),
        };

        $this->assertSame(
            in_array($profile, self::MATRIX[$action], true),
            $allowed,
            "«{$profile}» ".(in_array($profile, self::MATRIX[$action], true) ? 'debería' : 'no debería')." poder «{$action}».",
        );
    }

    #[Test]
    public function nobody_schedules_in_the_past(): void
    {
        $this->assertFalse($this->policy->schedule($this->users['admin'], $this->content, now()->subMinute()));
        $this->assertFalse($this->policy->schedule($this->users['editor autor'], $this->content, now()->subMinute()));
    }

    #[Test]
    public function the_listing_is_for_admins_and_editors(): void
    {
        $this->assertTrue($this->policy->viewAny($this->users['admin']));
        $this->assertTrue($this->policy->viewAny($this->users['editor sin relación']));
        $this->assertFalse($this->policy->viewAny($this->users['usuario normal']));
    }

    #[Test]
    public function an_editor_creates_only_with_a_platform_and_only_in_theirs(): void
    {
        $withPlatform = $this->users['editor con la plataforma y sin relación'];
        $withoutPlatform = $this->users['editor sin relación'];
        $other = Platform::factory()->create();

        $this->assertTrue($this->policy->create($withPlatform));
        $this->assertFalse($this->policy->create($withoutPlatform));
        $this->assertFalse($this->policy->create($this->users['usuario normal']));

        $this->assertTrue($this->policy->createIn($withPlatform, $this->platform->id));
        $this->assertFalse($this->policy->createIn($withPlatform, $other->id));
        $this->assertTrue($this->policy->createIn($this->users['admin'], $other->id));
    }

    #[Test]
    public function a_content_without_a_platform_is_only_for_admins_unless_you_are_its_author(): void
    {
        $general = Content::factory()->create(['author_id' => $this->users['admin']->id, 'platform_id' => null]);

        $this->assertFalse($this->policy->update($this->users['editor con la plataforma y sin relación'], $general));
        $this->assertTrue($this->policy->update($this->users['admin'], $general));
    }
}
