<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Concerns\HandlesBatchImageUploads;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\File;
use App\Models\Gallery;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadWebpTest extends TestCase
{
    use HandlesBatchImageUploads;
    use HasImageFileUpload;
    use RefreshDatabase;

    private string $directory = 'pruebas-webp';

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]));

        $this->cleanDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanDirectory();

        parent::tearDown();
    }

    private function cleanDirectory(): void
    {
        foreach (['private', 'public'] as $scope) {
            $path = storage_path('app/'.$scope.'/'.$this->directory);

            if (is_dir($path)) {
                exec('rm -rf '.escapeshellarg($path));
            }

            $galleries = storage_path('app/'.$scope.'/galleries');
            if (is_dir($galleries)) {
                exec('rm -rf '.escapeshellarg($galleries));
            }
        }
    }

    public function test_resolve_image_upload_converts_to_webp_by_default(): void
    {
        $upload = UploadedFile::fake()->image('tecnologia.jpg', 600, 400);

        $data = ['image_id' => $upload];
        $resolved = $this->resolveImageUpload($data, 'image_id', $this->directory);

        $this->assertNotNull($resolved['image_id']);
        $file = File::find($resolved['image_id']);

        $this->assertNotNull($file);
        $this->assertSame('image/webp', $file->fileType?->mime);
        $this->assertStringEndsWith('.webp', $file->name);
        $this->assertFileExists($file->storagePathFile);
    }

    public function test_batch_image_uploads_converts_to_webp(): void
    {
        $gallery = Gallery::create([
            'user_id' => auth()->id(),
            'name' => 'Galería de prueba',
            'aspect_ratio' => '16:9',
        ]);

        $image1 = UploadedFile::fake()->image('foto1.png', 800, 600);
        $image2 = UploadedFile::fake()->image('foto2.jpg', 800, 600);

        $this->attachBatchImagesToGallery([$image1, $image2], $gallery);

        $gallery->refresh();
        $this->assertCount(2, $gallery->images);

        foreach ($gallery->images as $galleryImage) {
            $this->assertSame('image/webp', $galleryImage->image?->fileType?->mime);
            $this->assertStringEndsWith('.webp', (string) $galleryImage->image?->name);
        }
    }

    public function test_user_avatar_converts_to_webp_on_save(): void
    {
        $disk = Storage::disk('public');
        $filename = 'avatar-test.jpg';

        $image = imagecreatetruecolor(800, 800);
        ob_start();
        imagejpeg($image);
        $content = (string) ob_get_clean();
        imagedestroy($image);

        $disk->put('profile-photos/'.$filename, $content);

        /** @var User $user */
        $user = User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
            'profile_photo_path' => 'profile-photos/'.$filename,
        ]);

        $user->refresh();

        $this->assertStringEndsWith('.webp', (string) $user->profile_photo_path);
        $this->assertTrue($disk->exists((string) $user->profile_photo_path));
        $this->assertFalse($disk->exists('profile-photos/'.$filename));

        // Limpiar
        if ($user->profile_photo_path) {
            $disk->delete($user->profile_photo_path);
        }
    }

    public function test_command_converts_existing_images_to_webp(): void
    {
        // Crear un archivo no webp forzando webpOriginal: false
        $upload = UploadedFile::fake()->image('antigua.jpg', 600, 400);
        $file = File::addFile($upload, $this->directory, webpOriginal: false);

        $this->assertNotNull($file);
        $this->assertSame('image/jpeg', $file->fileType?->mime);
        $this->assertStringEndsWith('.jpg', $file->name);

        // Probar dry-run
        $this->artisan('files:convert-to-webp', ['--dry-run' => true])
            ->assertSuccessful();

        $file->refresh();
        $this->assertSame('image/jpeg', $file->fileType?->mime);

        // Ejecutar conversión real
        $this->artisan('files:convert-to-webp')
            ->assertSuccessful();

        $file->refresh();
        $this->assertSame('image/webp', $file->fileType?->mime);
        $this->assertStringEndsWith('.webp', $file->name);
        $this->assertFileExists($file->storagePathFile);
    }
}
