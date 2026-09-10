<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Tests\TestCase;

/**
 * Red de seguridad contra los campos que se pierden al guardar.
 *
 * Un campo de formulario que existe en la tabla pero no es `fillable` no da
 * error: Eloquent lo descarta y ya está. El usuario rellena el campo, le da a
 * guardar, la pantalla dice que todo ha ido bien y el dato no se ha escrito.
 * Es de los fallos más difíciles de ver, porque no deja rastro en ningún log.
 *
 * Así estaban `FileTypeResource` (los cuatro iconos, descartados en silencio) y
 * `HardwareAvailableComponentResource` (el formulario entero, que además
 * reventaba con MassAssignmentException). Ninguno de los dos lo detectó ninguna
 * auditoría: no hay forma de verlo leyendo el Resource ni leyendo el modelo,
 * sólo cruzando los dos.
 *
 * Estos tests hacen ese cruce automáticamente para todo el panel.
 */
class AssignableFieldsTest extends TestCase
{
    use RefreshDatabase;

    /** Componentes de Filament que escriben un valor en el modelo. */
    private const INPUTS = 'TextInput|Textarea|Select|Toggle|Checkbox|DatePicker|DateTimePicker|'
        .'TimePicker|FileUpload|ImageCropperUpload|RichEditor|MarkdownEditor|ColorPicker|KeyValue|'
        .'Repeater|TagsInput|Radio|CheckboxList|EditorJsField|YoutubeVideoField|Hidden|MultiSelect';

    /** Columnas que gestiona Eloquent y nunca deben ser asignables. */
    private const NON_ASSIGNABLE = ['id', 'created_at', 'updated_at', 'deleted_at'];

    #[Test]
    public function no_form_field_in_the_panel_is_discarded_on_save(): void
    {
        $lost = [];

        foreach ($this->filamentClasses() as $class) {
            if (! method_exists($class, 'getModel')) {
                continue;
            }

            // Si las Pages del Resource persisten a mano no hay asignación
            // masiva que valga: los campos del formulario son entrada de una
            // lógica propia, no columnas que Eloquent vaya a escribir. Es el
            // caso de los tokens de API, que se emiten por DeviceTokenService.
            if ($this->persistsManually($class)) {
                continue;
            }

            try {
                $modelClass = $class::getModel();
            } catch (\Throwable) {
                continue;
            }

            if (! class_exists($modelClass)) {
                continue;
            }

            $model = new $modelClass;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);

            foreach ($this->formFields($class) as $field) {
                if (! in_array($field, $columns, true)) {
                    // No es columna: relación, campo virtual o estado del
                    // formulario. No hay nada que guardar.
                    continue;
                }

                if (in_array($field, self::NON_ASSIGNABLE, true)) {
                    continue;
                }

                if ($model->isFillable($field)) {
                    continue;
                }

                $lost[] = $this->qualifiedName($class)." → {$table}.{$field}";
            }
        }

        $this->assertSame([], $lost, sprintf(
            "Hay %d campo(s) de formulario que Eloquent descartaría al guardar.\n".
            "Añádelos al \$fillable de su modelo, o quítalos del formulario:\n  - %s\n",
            count($lost),
            implode("\n  - ", $lost)
        ));
    }

    #[Test]
    public function every_model_declares_its_mass_assignment_policy(): void
    {
        // Sin `$fillable` ni `$guarded` propios, Eloquent aplica su
        // `$guarded = ['*']` y el modelo no admite asignación masiva en
        // absoluto: cualquier create() revienta con MassAssignmentException.
        // Es un despiste fácil, porque el modelo se lee igual de bien.
        $silent = [];

        foreach ($this->modelClasses() as $class) {
            $model = new $class;

            if ($model->getFillable() === [] && $model->getGuarded() === ['*']) {
                $silent[] = class_basename($class);
            }
        }

        $this->assertSame([], $silent, sprintf(
            'Estos modelos no declaran $fillable ni $guarded, así que no admiten '.
            "asignación masiva:\n  - %s\n",
            implode("\n  - ", $silent)
        ));
    }

    /**
     * ¿El Resource escribe sus registros por su cuenta?
     *
     * Se mira si alguna de sus Pages define `handleRecordCreation()` o
     * `handleRecordUpdate()`: con eso, la persistencia no pasa por `fill()` y
     * el `$fillable` del modelo no pinta nada.
     */
    private function persistsManually(string $class): bool
    {
        $path = (new ReflectionClass($class))->getFileName();

        if ($path === false) {
            return false;
        }

        $pagesDirectory = dirname($path).'/Pages';

        if (! is_dir($pagesDirectory)) {
            return false;
        }

        foreach (glob($pagesDirectory.'/*.php') ?: [] as $page) {
            $src = file_get_contents($page);

            if (str_contains($src, 'handleRecordCreation') || str_contains($src, 'handleRecordUpdate')) {
                return true;
            }
        }

        return false;
    }

    /** Nombre con panel incluido: hay dos ApiTokenResource, Admin y Tenant. */
    private function qualifiedName(string $class): string
    {
        $parts = explode('\\', $class);
        $panel = $parts[2] ?? '';

        return $panel.'/'.class_basename($class);
    }

    /** @return list<class-string> */
    private function filamentClasses(): array
    {
        $classes = [];

        foreach ($this->phpFiles('app/Filament') as $path) {
            $name = basename($path);

            if (! str_ends_with($name, 'Resource.php') && ! str_ends_with($name, 'RelationManager.php')) {
                continue;
            }

            $class = $this->classFrom($path);

            if ($class !== null) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /** @return list<class-string<Model>> */
    private function modelClasses(): array
    {
        $classes = [];

        foreach ($this->phpFiles('app/Models') as $path) {
            $class = $this->classFrom($path);

            if ($class === null) {
                continue;
            }

            $r = new ReflectionClass($class);

            if ($r->isAbstract() || ! $r->isSubclassOf(Model::class)) {
                continue;
            }

            // BaseModel no tiene tabla propia: es la raíz de la jerarquía.
            if (! Schema::hasTable((new $class)->getTable())) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Campos declarados en el bloque `form()` de una clase de Filament.
     *
     * Se lee el fichero en vez de instanciar el schema porque construirlo
     * necesita el contexto de Livewire, y aquí sólo hacen falta los nombres.
     *
     * @return list<string>
     */
    private function formFields(string $class): array
    {
        $path = (new ReflectionClass($class))->getFileName();

        if ($path === false) {
            return [];
        }

        $src = file_get_contents($path);
        $start = stripos($src, 'function form');

        if ($start === false) {
            return [];
        }

        // El formulario acaba donde empieza la tabla; si no hay tabla, en el
        // final del fichero.
        $end = stripos($src, 'function table', $start);
        $body = substr($src, $start, $end === false ? null : $end - $start);

        preg_match_all(
            '/(?:'.self::INPUTS.")::(?:make|makeImage)\(\s*'([a-z0-9_]+)'/i",
            $body,
            $found
        );

        return array_values(array_unique($found[1]));
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $paths = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($it as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $paths[] = $file->getPathname();
        }

        return $paths;
    }

    private function classFrom(string $path): ?string
    {
        $src = file_get_contents($path);

        if (! preg_match('/namespace ([^;]+);/', $src, $m)) {
            return null;
        }

        $class = $m[1].'\\'.basename($path, '.php');

        return class_exists($class) ? $class : null;
    }
}
