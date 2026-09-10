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
 * El panel tiene que enseñar la imagen que ya está guardada, dentro del propio
 * campo de subida.
 *
 * Los recursos suben imágenes con un `FileUpload` apuntando a `image_id`, una
 * clave foránea, no una ruta de disco. Sin más, Filament trata el id como si
 * fuera una ruta dentro del disco configurado, nunca la encuentra, y el campo
 * se enseña vacío aunque el registro sí tenga imagen.
 *
 * Antes esto se tapaba con un componente aparte (`CurrentImage`) que pintaba la
 * imagen guardada **encima** del uploader, que seguía vacío: no se podía
 * recortar, quitar ni reemplazar la imagen actual con los controles nativos de
 * Filament, y guardar sin tocar nada daba la sensación de que no había pasado
 * nada porque la miniatura de arriba no cambiaba.
 *
 * `ImageCropperUpload::asFileRecord()` lo resuelve en el campo mismo: hace que
 * Filament no compruebe el id contra el disco (`fetchFileInformation(false)`) y
 * resuelve la vista previa a mano desde el modelo `File`
 * (`getUploadedFileUsing()`). Con eso el propio campo ya sabe editar, recortar
 * y quitar la imagen guardada.
 *
 * Lo que se prueba aquí es la parte que no depende de FilePond (JavaScript):
 * que el estado del campo llega con el id real en vez de vacío, y que quitarlo
 * de verdad desvincula la FK al guardar. Se prueba sobre `TechnologyResource`
 * porque es uno de los que reportó el usuario; el campo es el mismo en los diez
 * recursos que usan `image_id`.
 */
class ExistingImagePreloadTest extends TestCase
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

    private function image(): File
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

    private function technology(string $name, ?int $imageId = null): Technology
    {
        return Technology::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'image_id' => $imageId,
        ]);
    }

    #[Test]
    public function the_edit_form_loads_the_saved_image_id_into_the_field(): void
    {
        $image = $this->image();
        $technology = $this->technology('Laravel', $image->id);

        // Antes `mutateFormDataBeforeFill()` la quitaba a propósito, porque el
        // campo no sabía qué hacer con un id. Ahora tiene que llegar.
        //
        // El estado del campo es un array aunque no sea `multiple()` —lo
        // desenvuelve `resolveImageUpload()` al guardar, no Filament al
        // hidratar—, con una clave generada (UUID) y no un índice numérico, así
        // que aquí se comprueban los valores y no la forma exacta del array.
        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->assertSet('data.image_id', fn (array $state): bool => array_values($state) === [$image->id]);
    }

    /**
     * Lo que de verdad ve FilePond: `getUploadedFiles()` es el método que la
     * propia librería llama al montar el campo para saber qué ya está subido.
     * Sin `asFileRecord()`, este método intentaría leer el id como una ruta del
     * disco configurado y no encontraría nada (`fetchFileInformation` por
     * defecto comprueba `Storage::disk(...)->exists($id)`, que es siempre falso
     * para un id numérico).
     *
     * `form.image_id` es la clave que Filament asigna al campo dentro del
     * schema por defecto de una página de edición.
     */
    #[Test]
    public function the_field_resolves_a_real_preview_for_the_saved_image(): void
    {
        $image = $this->image();
        $technology = $this->technology('Laravel', $image->id);

        $component = Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->instance();

        $uploadedFiles = $component->callSchemaComponentMethod('form.image_id', 'getUploadedFiles');

        $this->assertIsArray($uploadedFiles);
        $this->assertCount(1, $uploadedFiles);

        $preview = array_values($uploadedFiles)[0];

        $this->assertSame('logo.png', $preview['name']);
        $this->assertSame(2048, $preview['size']);
        $this->assertSame($image->url, $preview['url']);
    }

    #[Test]
    public function the_edit_form_has_no_image_id_when_the_record_has_none(): void
    {
        $withoutImage = $this->technology('Sin logo');

        Livewire::test(EditTechnology::class, ['record' => $withoutImage->getKey()])
            ->assertSuccessful()
            ->assertSet('data.image_id', []);
    }

    #[Test]
    public function the_create_form_does_not_break_without_a_record(): void
    {
        Livewire::test(CreateTechnology::class)
            ->assertSuccessful()
            ->assertSet('data.image_id', []);
    }

    /**
     * Lo que no puede pasar bajo ningún concepto: que abrir el formulario y
     * guardarlo sin tocar la imagen la desvincule.
     */
    #[Test]
    public function saving_without_touching_the_image_keeps_the_foreign_key(): void
    {
        $image = $this->image();
        $technology = $this->technology('Laravel', $image->id);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->fillForm(['name' => 'Laravel 13'])
            ->call('save')
            ->assertHasNoFormErrors();

        $technology->refresh();

        $this->assertSame('Laravel 13', $technology->name);
        $this->assertSame($image->id, $technology->image_id);
    }

    /**
     * Con el campo enseñando de verdad la imagen actual, un estado vacío ya no
     * es ambiguo: significa que el usuario la ha quitado con el botón nativo de
     * Filament. Antes esto se ignoraba (la FK se dejaba tal cual) porque un
     * campo vacío era indistinguible de uno que nunca había llegado a
     * rellenarse — el propio bug que arregla este cambio.
     */
    #[Test]
    public function removing_the_image_clears_the_foreign_key_on_save(): void
    {
        $image = $this->image();
        $technology = $this->technology('Laravel', $image->id);

        Livewire::test(EditTechnology::class, ['record' => $technology->getKey()])
            ->set('data.image_id', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $technology->refresh();

        $this->assertNull($technology->image_id);
    }
}
