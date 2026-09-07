<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Technologies\Pages\CreateTechnology;
use App\Filament\Admin\Resources\Technologies\Pages\EditTechnology;
use App\Models\File;
use App\Models\Technology;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El panel tiene que enseñar la imagen que ya está guardada.
 *
 * Los recursos suben imágenes con un `FileUpload` apuntando a `image_id`, una
 * clave foránea. Un `FileUpload` espera una ruta de disco, así que el estado
 * era el id numérico y el componente intentaba pintar «4» como si fuera una
 * ruta. Cada página de edición acabó haciendo `unset($data['image_id'])` en
 * `mutateFormDataBeforeFill()` para que no diera error, y con eso **el panel
 * dejó de enseñar la imagen guardada**: sólo el hueco vacío para subir otra.
 * En el frontend sí se veía, porque allí se resuelve la relación, y de ahí que
 * pareciera cosa del storage.
 *
 * `CurrentImage` la pinta resolviendo la relación, igual que el frontend.
 *
 * El otro riesgo de este arreglo, y el que de verdad haría daño, es que al
 * guardar sin tocar el campo de imagen se borrara la FK. Eso también está aquí.
 *
 * Se prueba sobre `TechnologyResource` porque es uno de los que reportó el
 * usuario; el componente es el mismo en los diez recursos que usan `image_id`.
 */
class CurrentImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();

        $this->actingAs(User::factory()->create([
            'role_id' => 1,
            'is_active' => true,
        ]));

        Filament::setServingStatus(true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function imagen(): File
    {
        return File::create([
            'module' => 'technologies',
            'path' => 'technologies',
            'storage_path' => 'public/technologies',
            'name' => 'logo.png',
            'original_name' => 'logo.png',
            'width' => 128,
            'height' => 128,
            'size' => 2048,
            'alt' => 'Logo',
            'title' => 'Logo',
            'is_private' => false,
        ]);
    }

    private function tecnologia(string $nombre, ?int $imagenId = null): Technology
    {
        return Technology::create([
            'name' => $nombre,
            'slug' => Str::slug($nombre),
            'image_id' => $imagenId,
        ]);
    }

    #[Test]
    public function el_formulario_de_edicion_pinta_la_imagen_guardada(): void
    {
        $imagen = $this->imagen();
        $tecnologia = $this->tecnologia('Laravel', $imagen->id);

        // La URL es la del controlador de ficheros (`route('file.get', …)`),
        // no una ruta de disco: es justo lo que el uploader no sabe pintar.
        Livewire::test(EditTechnology::class, ['record' => $tecnologia->getKey()])
            ->assertSee($imagen->thumbnail('medium'), escape: false);
    }

    #[Test]
    public function no_pinta_nada_cuando_el_registro_no_tiene_imagen(): void
    {
        $sinImagen = $this->tecnologia('Sin logo');

        Livewire::test(EditTechnology::class, ['record' => $sinImagen->getKey()])
            ->assertSuccessful()
            ->assertDontSee('/file/get');
    }

    #[Test]
    public function el_formulario_de_creacion_no_se_rompe_sin_registro(): void
    {
        Livewire::test(CreateTechnology::class)
            ->assertSuccessful()
            ->assertDontSee('/file/get');
    }

    /**
     * Lo que no puede pasar bajo ningún concepto: que abrir el formulario y
     * guardarlo sin tocar la imagen la desvincule.
     */
    #[Test]
    public function guardar_sin_tocar_la_imagen_conserva_la_clave_foranea(): void
    {
        $imagen = $this->imagen();
        $tecnologia = $this->tecnologia('Laravel', $imagen->id);

        Livewire::test(EditTechnology::class, ['record' => $tecnologia->getKey()])
            ->fillForm(['name' => 'Laravel 13'])
            ->call('save')
            ->assertHasNoFormErrors();

        $tecnologia->refresh();

        $this->assertSame('Laravel 13', $tecnologia->name);
        $this->assertSame($imagen->id, $tecnologia->image_id);
    }
}
