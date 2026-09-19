<?php

declare(strict_types=1);

namespace Tests\Feature\Galleries;

use App\Enums\GalleryAspectRatioEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\GalleriesRelationManager;
use App\Filament\Admin\Resources\Galleries\Pages\CreateGallery;
use App\Filament\Admin\Resources\Galleries\Pages\EditGallery;
use App\Filament\Admin\Resources\Galleries\RelationManagers\ImagesRelationManager;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\EditHardwareDevice;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Hardware\HardwareComponent;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();
    }

    private function createFakeFile(string $name = 'test.jpg'): File
    {
        return File::create([
            'module' => 'galleries',
            'path' => 'galleries',
            'storage_path' => 'public/galleries',
            'name' => $name,
            'original_name' => $name,
            'width' => 1600,
            'height' => 900,
            'size' => 2048,
            'alt' => 'Foto de prueba',
            'title' => 'Foto de prueba',
            'is_private' => false,
        ]);
    }

    public function test_gallery_aspect_ratio_enum_methods(): void
    {
        $this->assertSame('16:9', GalleryAspectRatioEnum::Wide16x9->value);
        $this->assertSame(['16:9'], GalleryAspectRatioEnum::Wide16x9->cropperAspectRatios());
        $this->assertArrayHasKey('16:9', GalleryAspectRatioEnum::options());
        $this->assertArrayHasKey('4:3', GalleryAspectRatioEnum::options());
        $this->assertArrayHasKey('1:1', GalleryAspectRatioEnum::options());
        $this->assertArrayHasKey('free', GalleryAspectRatioEnum::options());
    }

    public function test_can_create_gallery_with_multiple_images_and_auto_cover(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $file1 = UploadedFile::fake()->image('photo1.jpg', 1600, 900);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 1600, 900);

        Livewire::test(CreateGallery::class)
            ->fillForm([
                'name' => 'Mi galería de pruebas',
                'description' => 'Descripción de prueba',
                'aspect_ratio' => GalleryAspectRatioEnum::Wide16x9->value,
                'uploaded_images' => [$file1, $file2],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $gallery = Gallery::where('name', 'Mi galería de pruebas')->first();
        $this->assertNotNull($gallery);
        $this->assertSame(GalleryAspectRatioEnum::Wide16x9, $gallery->aspect_ratio);

        // Se deben haber creado 2 imágenes con orden correlativo
        $this->assertCount(2, $gallery->images);
        $this->assertSame(1, $gallery->images[0]->order);
        $this->assertSame(2, $gallery->images[1]->order);

        // La portada debe haberse asignado automáticamente a la primera foto
        $this->assertNotNull($gallery->image_id);
        $this->assertSame($gallery->images[0]->image_id, $gallery->image_id);
        $this->assertTrue($gallery->isCover((int) $gallery->images[0]->image_id));
        $this->assertFalse($gallery->isCover((int) $gallery->images[1]->image_id));
    }

    public function test_can_set_image_as_cover_via_action(): void
    {
        $gallery = Gallery::factory()->create();
        $file1 = $this->createFakeFile('foto1.jpg');
        $file2 = $this->createFakeFile('foto2.jpg');

        $img1 = GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file1->id,
            'order' => 1,
        ]);

        $img2 = GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file2->id,
            'order' => 2,
        ]);

        $gallery->update(['image_id' => $file1->id]);
        $this->assertTrue($gallery->isCover($file1->id));

        // Cambiar portada a la foto 2
        $gallery->update(['image_id' => $file2->id]);
        $this->assertTrue($gallery->fresh()->isCover($file2->id));
        $this->assertFalse($gallery->fresh()->isCover($file1->id));
    }

    public function test_gallery_images_are_ordered_chronologically_by_order_column(): void
    {
        $gallery = Gallery::factory()->create();
        $fileA = $this->createFakeFile('foto_a.jpg');
        $fileB = $this->createFakeFile('foto_b.jpg');
        $fileC = $this->createFakeFile('foto_c.jpg');

        GalleryImage::create(['gallery_id' => $gallery->id, 'image_id' => $fileC->id, 'order' => 30]);
        GalleryImage::create(['gallery_id' => $gallery->id, 'image_id' => $fileA->id, 'order' => 10]);
        GalleryImage::create(['gallery_id' => $gallery->id, 'image_id' => $fileB->id, 'order' => 20]);

        $orderedImages = $gallery->fresh()->images;

        $this->assertSame($fileA->id, $orderedImages[0]->image_id);
        $this->assertSame($fileB->id, $orderedImages[1]->image_id);
        $this->assertSame($fileC->id, $orderedImages[2]->image_id);
    }

    public function test_polymorphic_relations_with_content_page_and_hardware(): void
    {
        $gallery = Gallery::factory()->create();

        $content = Content::factory()->create();
        $page = ContentPage::create([
            'content_id' => $content->id,
            'title' => 'Página 1',
            'slug' => 'pagina-1',
            'order' => 1,
        ]);
        $hardware = HardwareDevice::factory()->create();

        // Asociar a Content
        $content->galleries()->attach($gallery->id, ['order' => 1]);
        $this->assertTrue($content->galleries->contains($gallery));
        $this->assertTrue($gallery->contents->contains($content));

        // Asociar a ContentPage
        $page->galleries()->attach($gallery->id, ['order' => 1]);
        $this->assertTrue($page->galleries->contains($gallery));
        $this->assertTrue($gallery->pages->contains($page));

        // Asociar a HardwareDevice
        $hardware->galleries()->attach($gallery->id, ['order' => 1]);
        $this->assertTrue($hardware->galleries->contains($gallery));
        $this->assertTrue($gallery->hardwareDevices->contains($hardware));

        // Asociar a HardwareComponent
        $component = HardwareComponent::create([
            'hardware_device_id' => $hardware->id,
            'name' => 'Sensor BME280',
        ]);
        $component->galleries()->attach($gallery->id, ['order' => 1]);
        $this->assertTrue($component->galleries->contains($gallery));
        $this->assertTrue($gallery->hardwareComponents->contains($component));
    }

    public function test_gallery_safe_delete_cleans_images(): void
    {
        $gallery = Gallery::factory()->create();
        $file1 = $this->createFakeFile('img1.jpg');
        $file2 = $this->createFakeFile('img2.jpg');

        $img1 = GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file1->id,
            'order' => 1,
        ]);

        $img2 = GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file2->id,
            'order' => 2,
        ]);

        $this->assertDatabaseHas('galleries', ['id' => $gallery->id]);
        $this->assertDatabaseHas('gallery_images', ['id' => $img1->id]);
        $this->assertDatabaseHas('gallery_images', ['id' => $img2->id]);

        $gallery->safeDelete();

        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
        $this->assertDatabaseMissing('gallery_images', ['id' => $img1->id]);
        $this->assertDatabaseMissing('gallery_images', ['id' => $img2->id]);
    }

    public function test_gallery_policy_authorization(): void
    {
        $owner = User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => true,
        ]);
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $superAdmin = User::factory()->create([
            'role_id' => UserRoleEnum::SuperAdmin->value,
            'is_active' => true,
        ]);

        $gallery = Gallery::factory()->create(['user_id' => $owner->id]);

        // Dueño puede ver, actualizar y borrar
        $this->assertTrue(Gate::forUser($owner)->allows('update', $gallery));
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $gallery));

        // Otro usuario no puede actualizar ni borrar
        $this->assertFalse(Gate::forUser($otherUser)->allows('update', $gallery));
        $this->assertFalse(Gate::forUser($otherUser)->allows('delete', $gallery));

        // Admin y SuperAdmin pueden
        $this->assertTrue(Gate::forUser($admin)->allows('update', $gallery));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $gallery));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $gallery));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('delete', $gallery));
    }

    public function test_filament_images_relation_manager_renders(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $gallery = Gallery::factory()->create(['user_id' => $admin->id]);
        $file = $this->createFakeFile('prueba.jpg');

        GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file->id,
            'order' => 1,
            'caption' => 'Foto de prueba',
        ]);

        $this->assertFalse(ImagesRelationManager::isLazy());

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $gallery,
            'pageClass' => EditGallery::class,
        ])
            ->assertSuccessful()
            ->assertTableActionExists('addImages')
            ->assertCanSeeTableRecords($gallery->images);

        $emptyGallery = Gallery::factory()->create(['user_id' => $admin->id]);
        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $emptyGallery,
            'pageClass' => EditGallery::class,
        ])
            ->assertSuccessful()
            ->assertTableActionExists('emptyAddImages');
    }

    public function test_set_as_cover_action_in_relation_manager_promotes_image_to_first_position(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $gallery = Gallery::factory()->create(['user_id' => $admin->id]);
        $file1 = $this->createFakeFile('foto1.jpg');
        $file2 = $this->createFakeFile('foto2.jpg');

        $img1 = GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file1->id,
            'order' => 1,
        ]);
        $img2 = GalleryImage::create([
            'gallery_id' => $gallery->id,
            'image_id' => $file2->id,
            'order' => 2,
        ]);
        $gallery->update(['image_id' => $file1->id]);

        Livewire::test(ImagesRelationManager::class, [
            'ownerRecord' => $gallery,
            'pageClass' => EditGallery::class,
        ])
            ->callTableAction('setAsCover', $img2)
            ->assertHasNoTableActionErrors();

        $gallery->refresh();
        $this->assertSame($file2->id, $gallery->image_id);
        $this->assertSame(1, $img2->fresh()->order);
        $this->assertSame(2, $img1->fresh()->order);
    }

    public function test_filament_galleries_relation_manager_renders_on_content(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $content = Content::factory()->create();
        $gallery = Gallery::factory()->create(['user_id' => $admin->id]);
        $content->galleries()->attach($gallery->id);

        Livewire::test(GalleriesRelationManager::class, [
            'ownerRecord' => $content,
            'pageClass' => EditContent::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($content->galleries);
    }

    public function test_filament_galleries_relation_manager_renders_on_hardware_device(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $device = HardwareDevice::factory()->create(['user_id' => $admin->id]);
        $gallery = Gallery::factory()->create(['user_id' => $admin->id]);
        $device->galleries()->attach($gallery->id);

        Livewire::test(\App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers\GalleriesRelationManager::class, [
            'ownerRecord' => $device,
            'pageClass' => EditHardwareDevice::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($device->galleries);
    }

    public function test_can_render_create_and_edit_gallery_pages_with_hardware_devices(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $device = HardwareDevice::factory()->create([
            'user_id' => $admin->id,
            'extra' => ['wifi_rssi' => -42, 'ram_mb' => 512],
        ]);

        Livewire::test(CreateGallery::class)
            ->assertSuccessful();

        $gallery = Gallery::factory()->create(['user_id' => $admin->id]);

        Livewire::test(EditGallery::class, ['record' => $gallery->id])
            ->assertSuccessful()
            ->fillForm([
                'hardwareDevices' => [$device->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($gallery->fresh()->hardwareDevices->contains($device));
    }

    public function test_batch_upload_handles_partial_invalid_files_gracefully(): void
    {
        $admin = User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => true,
        ]);
        $this->actingAs($admin);

        $validImage = UploadedFile::fake()->image('valida.jpg', 800, 600);

        Livewire::test(CreateGallery::class)
            ->fillForm([
                'name' => 'Galería mixta',
                'aspect_ratio' => GalleryAspectRatioEnum::Wide16x9->value,
                'uploaded_images' => [$validImage, 'livewire-tmp/archivo-perdido.jpg'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $gallery = Gallery::where('name', 'Galería mixta')->first();
        $this->assertNotNull($gallery);
        // La imagen válida sí se procesó y guardó
        $this->assertCount(1, $gallery->images);
        $this->assertNotNull($gallery->image_id);
    }
}
