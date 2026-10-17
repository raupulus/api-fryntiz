<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PlatformColorRoleEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Platforms\Pages\EditPlatform;
use App\Models\Platform;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los colores del tema de una plataforma: hasta 5, cada uno con su papel en
 * la web si se dice. Sólo sirven para generar imágenes con IA.
 */
class PlatformThemeColorsTest extends TestCase
{
    use RefreshDatabase;

    private Platform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(['role_id' => UserRoleEnum::Admin->value, 'is_active' => true]));

        // La factoría pone un dominio sin `https://`, que el formulario no acepta.
        $this->platform = Platform::factory()->create(['domain' => 'https://ejemplo.test']);
    }

    #[Test]
    public function the_platform_saves_up_to_five_theme_colors_with_their_role(): void
    {
        Livewire::test(EditPlatform::class, ['record' => $this->platform->getRouteKey()])
            ->fillForm(['theme_colors' => [
                ['hex' => '#0f172a', 'role' => 'background'],
                ['hex' => '#22C55E', 'role' => null],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([
            ['hex' => '#0F172A', 'role' => PlatformColorRoleEnum::Background],
            ['hex' => '#22C55E', 'role' => null],
        ], $this->platform->refresh()->themeColors());

        Livewire::test(EditPlatform::class, ['record' => $this->platform->getRouteKey()])
            ->fillForm(['theme_colors' => array_fill(0, 6, ['hex' => '#FFFFFF', 'role' => null])])
            ->call('save')
            ->assertHasFormErrors(['theme_colors']);

        Livewire::test(EditPlatform::class, ['record' => $this->platform->getRouteKey()])
            ->fillForm(['theme_colors' => [['hex' => 'rojo', 'role' => null]]])
            ->call('save')
            ->assertHasFormErrors();
    }

    #[Test]
    public function only_valid_colors_are_used(): void
    {
        $this->platform->update(['theme_colors' => [
            ['hex' => '#abcdef', 'role' => 'accent'],
            ['hex' => 'no-es-un-color'],
            ['hex' => '#123456', 'role' => 'inventado'],
            'basura',
        ]]);

        $this->assertSame([
            ['hex' => '#ABCDEF', 'role' => PlatformColorRoleEnum::Accent],
            ['hex' => '#123456', 'role' => null],
        ], $this->platform->refresh()->themeColors());

        $this->assertSame([], Platform::factory()->create()->themeColors());
    }
}
