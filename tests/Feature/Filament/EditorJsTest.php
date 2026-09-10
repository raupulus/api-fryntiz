<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Admin\EditorJsController;
use App\Models\File;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los endpoints que Editor.js necesita del servidor.
 *
 * Las herramientas `image`, `attaches` y `linkTool` no funcionan sin esto, y por
 * eso se quedaron fuera al migrar el editor a Filament: en v2 sólo quedó
 * `SimpleImage`, que guarda la imagen **incrustada en el JSON como base64**.
 *
 * El de metadatos hace una petición saliente a una URL que elige quien escribe,
 * o sea SSRF si se deja abierto. La mitad de este fichero es eso.
 */
class EditorJsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
    }

    private function asEditor(): User
    {
        $user = User::factory()->create([
            'role_id' => UserRoleEnum::Editor->value,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        return $user;
    }

    // ── Subida ───────────────────────────────────────────────────────────────

    #[Test]
    public function it_uploads_an_image_and_leaves_it_in_the_files_module(): void
    {
        $this->asEditor();

        $response = $this->post(route('admin.editorjs.upload'), [
            'file' => UploadedFile::fake()->image('foto.jpg', 800, 600),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonStructure(['success', 'file' => ['url', 'name', 'size', 'file_id']]);

        // Lo que `SimpleImage` no hacía: dejar una fila de verdad, que se ve en
        // el panel y se sirve por el controlador de ficheros.
        $this->assertNotNull(File::find($response->json('file.file_id')));
        $this->assertStringNotContainsString('base64', (string) $response->json('file.url'));
    }

    #[Test]
    public function without_a_file_it_responds_with_a_validation_error(): void
    {
        $this->asEditor();

        $this->postJson(route('admin.editorjs.upload'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    #[Test]
    public function uploading_requires_being_authenticated(): void
    {
        $this->post(route('admin.editorjs.upload'), [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertRedirect();
    }

    #[Test]
    public function a_regular_user_cannot_upload_anything(): void
    {
        $this->actingAs(User::factory()->create([
            'role_id' => UserRoleEnum::User->value,
            'is_active' => true,
        ]));

        $this->post(route('admin.editorjs.upload'), [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertForbidden();
    }

    #[Test]
    public function a_deactivated_account_cannot_either(): void
    {
        $this->actingAs(User::factory()->create([
            'role_id' => UserRoleEnum::Admin->value,
            'is_active' => false,
        ]));

        $this->post(route('admin.editorjs.upload'), [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertForbidden();
    }

    // ── Metadatos de una URL ─────────────────────────────────────────────────

    /**
     * El dominio de prueba no existe, así que la resolución de nombres se
     * sustituye por una IP pública. Sin esto el caso feliz dependería de que
     * haya DNS y de que el dominio exista de verdad.
     */
    private function withFakeDns(): void
    {
        $this->app->bind(EditorJsController::class, fn () => new class extends EditorJsController
        {
            protected function resolver(string $host): array
            {
                return ['93.184.216.34'];
            }
        });
    }

    #[Test]
    public function it_reads_the_title_and_description_of_a_page(): void
    {
        $this->asEditor();
        $this->withFakeDns();

        Http::fake([
            '*' => Http::response(
                '<html><head><title>Una página</title>'
                .'<meta name="description" content="Lo que cuenta">'
                .'<meta property="og:image" content="https://ejemplo.test/foto.jpg">'
                .'</head></html>',
                200,
            ),
        ]);

        $this->getJson(route('admin.editorjs.url-metadata', ['url' => 'https://ejemplo.test/pagina']))
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonPath('meta.title', 'Una página')
            ->assertJsonPath('meta.description', 'Lo que cuenta')
            ->assertJsonPath('meta.image.url', 'https://ejemplo.test/foto.jpg');
    }

    /**
     * Lo que no puede pasar: que el editor sirva para leer la red interna.
     * `169.254.169.254` es el servicio de metadatos de media nube.
     *
     * @return list<array{string}>
     */
    public static function forbiddenUrls(): array
    {
        return [
            'bucle' => ['http://127.0.0.1:9200/'],
            'localhost' => ['http://localhost/admin'],
            'red privada' => ['http://192.168.1.1/'],
            'metadatos de nube' => ['http://169.254.169.254/latest/meta-data/'],
            'fichero local' => ['file:///etc/passwd'],
            'otro esquema' => ['gopher://ejemplo.test/'],
            'sin esquema' => ['ejemplo.test'],
            'vacía' => [''],
        ];
    }

    #[Test]
    #[DataProvider('forbiddenUrls')]
    public function it_does_not_reach_out_to_the_internal_network(string $url): void
    {
        $this->asEditor();

        Http::fake();

        $this->getJson(route('admin.editorjs.url-metadata', ['url' => $url]))
            ->assertOk()
            ->assertJsonPath('success', 0)
            ->assertJsonPath('meta', []);

        // Y lo que importa: no ha salido ninguna petición.
        Http::assertNothingSent();
    }

    #[Test]
    public function a_failing_response_does_not_blow_up(): void
    {
        $this->asEditor();

        $this->withFakeDns();

        Http::fake(['*' => Http::response('', 500)]);

        $this->getJson(route('admin.editorjs.url-metadata', ['url' => 'https://ejemplo.test/']))
            ->assertOk()
            ->assertJsonPath('success', 0);
    }

    #[Test]
    public function requesting_metadata_requires_permission(): void
    {
        $this->getJson(route('admin.editorjs.url-metadata', ['url' => 'https://ejemplo.test/']))
            ->assertUnauthorized();
    }
}
