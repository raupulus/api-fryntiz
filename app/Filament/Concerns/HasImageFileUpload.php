<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\File;
use Illuminate\Http\UploadedFile;

/**
 * Trait para persistir uploads de imágenes como registros en la tabla `files`.
 *
 * Usar en las Pages CreateRecord / EditRecord donde el campo sea una FK `*_id`
 * a la tabla `files`. El campo del formulario debe usar
 * `ImageCropperUpload::makeImage(...)->asFileRecord()->storeFiles(false)`:
 * `asFileRecord()` hace que el campo enseñe la imagen ya guardada (ver su
 * PHPDoc), y `storeFiles(false)` conserva el `UploadedFile` temporal en el
 * estado en vez de una ruta ya almacenada, que es lo que espera este trait.
 */
trait HasImageFileUpload
{
    /**
     * Convierte el valor de un campo de subida en el id de un registro `files`.
     *
     * - Si el valor es un UploadedFile, lo almacena con File::addFile y deja el id.
     * - Si el valor está vacío —el campo ya no trae ni el id antiguo ni uno
     *   nuevo—, es que se ha quitado con el botón nativo de Filament: se deja
     *   la FK a `null`. Antes esto se ignoraba (`unset`) para no pisar el valor
     *   ya guardado, pero eso era un parche del propio bug de precarga: con el
     *   campo enseñando de verdad la imagen actual (ver `ImageCropperUpload::asFileRecord()`),
     *   un campo vacío significa que el usuario la ha quitado a propósito, y
     *   dejarlo tal cual haría que «Quitar» no sirviera para nada.
     * - Si ya es un id (int) sin tocar, lo deja tal cual.
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
            $data[$field] = null;
        }

        return $data;
    }
}
