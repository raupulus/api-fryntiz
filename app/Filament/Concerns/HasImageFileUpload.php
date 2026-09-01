<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\File;
use Illuminate\Http\UploadedFile;

/**
 * Trait para persistir uploads de imágenes como registros en la tabla `files`.
 *
 * Usar en las Pages CreateRecord / EditRecord donde el campo sea una FK `*_id`
 * a la tabla `files`. El campo del formulario debe usar `->storeFiles(false)`
 * para que el estado conserve el UploadedFile temporal de Livewire en lugar de
 * una ruta ya almacenada.
 */
trait HasImageFileUpload
{
    /**
     * Convierte el valor de un campo de subida en el id de un registro `files`.
     *
     * - Si el valor es un UploadedFile, lo almacena con File::addFile y deja el id.
     * - Si el valor está vacío, elimina la clave para no sobrescribir la FK.
     * - Si ya es un id (int), lo deja tal cual.
     *
     * `$validate` viaja tal cual a `File::addFile()`. Se deja en `true` en los
     * campos que esperan una imagen —que hoy son todos los que usan este
     * trait—, y se pasa `false` allí donde no hay tipo que exigir: el editor de
     * contenido y los archivos adjuntos, donde se sube lo que haga falta.
     */
    protected function resolveImageUpload(array $data, string $field, string $module, bool $isPrivate = false, bool $validate = true): array
    {
        $value = $data[$field] ?? null;

        // Filament puede entregar el upload dentro de un array.
        if (is_array($value)) {
            $value = reset($value) ?: null;
        }

        if ($value instanceof UploadedFile) {
            $file = File::addFile($value, $module, $isPrivate, validate: $validate);
            $data[$field] = $file?->id;

            return $data;
        }

        if (blank($value)) {
            unset($data[$field]);
        }

        return $data;
    }
}
