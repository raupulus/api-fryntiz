<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Models\Platform;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsProductionContentStatuses;

/**
 * El menú del panel tiene una entrada por plataforma que abre «Contenidos»
 * filtrado por ella, y marca la plataforma en la que se está (auditoría del
 * editor del 2026-10-14). Antes se marcaba siempre «Contenidos».
 */
class AdminPlatformNavigationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProductionContentStatuses;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        $this->seedContentStatusesAsProduction();
        $this->actingAs(User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]));

        $this->platform = Platform::factory()->create(['title' => 'La Guía Linux']);
        Platform::factory()->create(['title' => 'Portfolio']);
    }

    // Una petición por test: Filament guarda la petición original
    // (`original_request()`) mientras dura la aplicación, y en un test la
    // segunda petición veía la primera.

    #[Test]
    public function the_platform_being_listed_is_the_active_menu_entry(): void
    {
        $url = ContentResource::getUrl('index').'?'.http_build_query(['filters' => ['platform_id' => ['value' => $this->platform->id]]]);

        $this->assertSame(['La Guía Linux'], $this->activeMenuEntries($url));
    }

    #[Test]
    public function without_a_platform_the_active_entry_is_contents(): void
    {
        $this->assertSame(['Contenidos'], $this->activeMenuEntries(ContentResource::getUrl('index')));
    }

    /**
     * @return list<string>
     */
    private function activeMenuEntries(string $url): array
    {
        $html = (string) $this->get($url)->assertOk()->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        return array_values(array_map(
            fn ($link): string => trim((string) preg_replace('/\s+/', ' ', (string) $link->textContent)),
            iterator_to_array($document->querySelectorAll('.fi-sidebar-item a[aria-current="page"]')),
        ));
    }
}
