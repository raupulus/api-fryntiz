<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Gdacs\GdacsEvents\GdacsEventResource;
use App\Filament\Admin\Widgets\GdacsActiveEventsWidget;
use App\Models\Gdacs\GdacsEvent;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdacsEventResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function actAsRole(UserRoleEnum $role): User
    {
        $user = User::factory()->create([
            'role_id' => $role->value,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    #[Test]
    public function an_admin_sees_the_events_list(): void
    {
        $this->actAsRole(UserRoleEnum::Admin);

        $this->get(GdacsEventResource::getUrl('index', panel: 'admin'))
            ->assertSuccessful();
    }

    #[Test]
    public function an_editor_cannot_open_the_events_list(): void
    {
        $this->actAsRole(UserRoleEnum::Editor);

        $this->get(GdacsEventResource::getUrl('index', panel: 'admin'))
            ->assertForbidden();
    }

    #[Test]
    public function only_index_and_no_create_or_edit_pages_exist(): void
    {
        $this->assertSame(['index'], array_keys(GdacsEventResource::getPages()));
    }

    #[Test]
    public function the_active_events_widget_only_shows_up_when_something_is_active(): void
    {
        $admin = $this->actAsRole(UserRoleEnum::Admin);
        $this->actingAs($admin);

        $this->assertFalse(GdacsActiveEventsWidget::canView());

        GdacsEvent::factory()->create(['is_current' => true]);

        $this->assertTrue(GdacsActiveEventsWidget::canView());
    }
}
