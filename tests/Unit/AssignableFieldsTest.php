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
    private const ENTRADAS = 'TextInput|Textarea|Select|Toggle|Checkbox|DatePicker|DateTimePicker|'
        .'TimePicker|FileUpload|ImageCropperUpload|RichEditor|MarkdownEditor|ColorPicker|KeyValue|'
        .'Repeater|TagsInput|Radio|CheckboxList|EditorJsField|YoutubeVideoField|Hidden|MultiSelect';

    /** Columnas que gestiona Eloquent y nunca deben ser asignables. */
    private const NO_ASIGNABLES = ['id', 'created_at', 'updated_at', 'deleted_at'];

    #[Test]
    public function ningun_campo_de_formulario_del_panel_se_descarta_al_guardar(): void
    {
        $perdidos = [];

        foreach ($this->clasesDeFilament() as $class) {
            if (! method_exists($class, 'getModel')) {
                continue;
            }

            // Si las Pages del Resource persisten a mano no hay asignación
            // masiva que valga: los campos del formulario son entrada de una
            // lógica propia, no columnas que Eloquent vaya a escribir. Es el
            // caso de los tokens de API, que se emiten por DeviceTokenService.
            if ($this->persisteAMano($class)) {
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
            $tabla = $model->getTable();

            if (! Schema::hasTable($tabla)) {
                continue;
            }

            $columnas = Schema::getColumnListing($tabla);

            foreach ($this->camposDelFormulario($class) as $campo) {
                if (! in_array($campo, $columnas, true)) {
                    // No es columna: relación, campo virtual o estado del
                    // formulario. No hay nada que guardar.
                    continue;
                }

                if (in_array($campo, self::NO_ASIGNABLES, true)) {
                    continue;
                }

                if ($model->isFillable($campo)) {
                    continue;
                }

                $perdidos[] = $this->nombreLargo($class)." → {$tabla}.{$campo}";
            }
        }

        $this->assertSame([], $perdidos, sprintf(
            "Hay %d campo(s) de formulario que Eloquent descartaría al guardar.\n".
            "Añádelos al \$fillable de su modelo, o quítalos del formulario:\n  - %s\n",
            count($perdidos),
            implode("\n  - ", $perdidos)
        ));
    }

    #[Test]
    public function todo_modelo_declara_su_politica_de_asignacion_masiva(): void
    {
        // Sin `$fillable` ni `$guarded` propios, Eloquent aplica su
        // `$guarded = ['*']` y el modelo no admite asignación masiva en
        // absoluto: cualquier create() revienta con MassAssignmentException.
        // Es un despiste fácil, porque el modelo se lee igual de bien.
        $mudos = [];

        foreach ($this->clasesDeModelos() as $class) {
            $model = new $class;

            if ($model->getFillable() === [] && $model->getGuarded() === ['*']) {
                $mudos[] = class_basename($class);
            }
        }

        $this->assertSame([], $mudos, sprintf(
            'Estos modelos no declaran $fillable ni $guarded, así que no admiten '.
            "asignación masiva:\n  - %s\n",
            implode("\n  - ", $mudos)
        ));
    }

    /**
     * ¿El Resource escribe sus registros por su cuenta?
     *
     * Se mira si alguna de sus Pages define `handleRecordCreation()` o
     * `handleRecordUpdate()`: con eso, la persistencia no pasa por `fill()` y
     * el `$fillable` del modelo no pinta nada.
     */
    private function persisteAMano(string $class): bool
    {
        $ruta = (new ReflectionClass($class))->getFileName();

        if ($ruta === false) {
            return false;
        }

        $directorioPages = dirname($ruta).'/Pages';

        if (! is_dir($directorioPages)) {
            return false;
        }

        foreach (glob($directorioPages.'/*.php') ?: [] as $page) {
            $src = file_get_contents($page);

            if (str_contains($src, 'handleRecordCreation') || str_contains($src, 'handleRecordUpdate')) {
                return true;
            }
        }

        return false;
    }

    /** Nombre con panel incluido: hay dos ApiTokenResource, Admin y Tenant. */
    private function nombreLargo(string $class): string
    {
        $partes = explode('\\', $class);
        $panel = $partes[2] ?? '';

        return $panel.'/'.class_basename($class);
    }

    /** @return list<class-string> */
    private function clasesDeFilament(): array
    {
        $clases = [];

        foreach ($this->ficherosPhp('app/Filament') as $ruta) {
            $nombre = basename($ruta);

            if (! str_ends_with($nombre, 'Resource.php') && ! str_ends_with($nombre, 'RelationManager.php')) {
                continue;
            }

            $class = $this->claseDe($ruta);

            if ($class !== null) {
                $clases[] = $class;
            }
        }

        return $clases;
    }

    /** @return list<class-string<Model>> */
    private function clasesDeModelos(): array
    {
        $clases = [];

        foreach ($this->ficherosPhp('app/Models') as $ruta) {
            $class = $this->claseDe($ruta);

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

            $clases[] = $class;
        }

        return $clases;
    }

    /**
     * Campos declarados en el bloque `form()` de una clase de Filament.
     *
     * Se lee el fichero en vez de instanciar el schema porque construirlo
     * necesita el contexto de Livewire, y aquí sólo hacen falta los nombres.
     *
     * @return list<string>
     */
    private function camposDelFormulario(string $class): array
    {
        $ruta = (new ReflectionClass($class))->getFileName();

        if ($ruta === false) {
            return [];
        }

        $src = file_get_contents($ruta);
        $inicio = stripos($src, 'function form');

        if ($inicio === false) {
            return [];
        }

        // El formulario acaba donde empieza la tabla; si no hay tabla, en el
        // final del fichero.
        $fin = stripos($src, 'function table', $inicio);
        $cuerpo = substr($src, $inicio, $fin === false ? null : $fin - $inicio);

        preg_match_all(
            '/(?:'.self::ENTRADAS.")::(?:make|makeImage)\(\s*'([a-z0-9_]+)'/i",
            $cuerpo,
            $encontrados
        );

        return array_values(array_unique($encontrados[1]));
    }

    /** @return list<string> */
    private function ficherosPhp(string $directorio): array
    {
        if (! is_dir($directorio)) {
            return [];
        }

        $rutas = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));

        foreach ($it as $fichero) {
            if ($fichero->isDir() || ! str_ends_with($fichero->getFilename(), '.php')) {
                continue;
            }

            $rutas[] = $fichero->getPathname();
        }

        return $rutas;
    }

    private function claseDe(string $ruta): ?string
    {
        $src = file_get_contents($ruta);

        if (! preg_match('/namespace ([^;]+);/', $src, $m)) {
            return null;
        }

        $class = $m[1].'\\'.basename($ruta, '.php');

        return class_exists($class) ? $class : null;
    }
}
