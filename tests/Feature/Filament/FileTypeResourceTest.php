<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\FileTypes\FileTypeResource;
use App\Filament\Admin\Resources\FileTypes\Pages\CreateFileType;
use App\Filament\Admin\Resources\FileTypes\Pages\EditFileType;
use App\Filament\Admin\Resources\FileTypes\Pages\ListFileTypes;
use App\Models\FileType;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileTypeResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->superadmin = User::factory()->create([
            'role_id' => UserRoleEnum::SuperAdmin->value,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function administrator_can_list_file_types(): void
    {
        FileType::create([
            'user_id' => $this->admin->id,
            'type' => 'documento',
            'extension' => 'pdf',
            'mime' => 'application/pdf',
        ]);

        $this->actingAs($this->admin)
            ->get(FileTypeResource::getUrl('index'))
            ->assertOk()
            ->assertSee('.pdf')
            ->assertSee('application/pdf');
    }

    #[Test]
    public function non_administrators_cannot_access_file_types(): void
    {
        $user = User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(FileTypeResource::getUrl('index'))
            ->assertForbidden();

        $editor = User::factory()->create([
            'role_id' => UserRoleEnum::Editor->value,
            'is_active' => true,
        ]);

        $this->actingAs($editor)
            ->get(FileTypeResource::getUrl('index'))
            ->assertForbidden();
    }

    #[Test]
    public function table_icon_column_uses_public_disk_and_resolves_image_url(): void
    {
        Storage::fake('public');

        // Store a fake 32px icon in public disk
        Storage::disk('public')->put('icons/file_types/sample_32.webp', 'fake content');

        $fileType = FileType::create([
            'user_id' => $this->admin->id,
            'type' => 'imagen',
            'extension' => 'png',
            'mime' => 'image/png',
            'icon32' => 'icons/file_types/sample_32.webp',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListFileTypes::class)
            ->assertCanSeeTableRecords([$fileType]);

        $this->assertTrue(Storage::disk('public')->exists($fileType->icon32));
    }

    #[Test]
    public function edit_and_create_pages_use_full_content_width(): void
    {
        $editPage = new EditFileType;
        $createPage = new CreateFileType;

        $this->assertSame(Width::Full, $editPage->getMaxContentWidth());
        $this->assertSame(Width::Full, $createPage->getMaxContentWidth());
    }

    #[Test]
    public function administrator_can_view_edit_page(): void
    {
        $fileType = FileType::create([
            'user_id' => $this->admin->id,
            'type' => 'documento',
            'extension' => 'docx',
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $this->actingAs($this->admin)
            ->get(FileTypeResource::getUrl('edit', ['record' => $fileType]))
            ->assertOk()
            ->assertSee('docx');
    }

    #[Test]
    public function administrator_can_update_file_type_via_form(): void
    {
        $fileType = FileType::create([
            'user_id' => $this->admin->id,
            'type' => 'documento',
            'extension' => 'txt',
            'mime' => 'text/plain',
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditFileType::class, ['record' => $fileType->getRouteKey()])
            ->fillForm([
                'type' => 'código',
                'extension' => 'txt',
                'mime' => 'text/plain',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('file_types', [
            'id' => $fileType->id,
            'type' => 'código',
        ]);
    }
}
