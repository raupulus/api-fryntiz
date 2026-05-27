<?php

namespace App\Filament\Concerns;

use App\Models\File;

/**
 * Trait para persistir uploads de imágenes como registros en la tabla `files`.
 *
 * Usar en las Pages CreateRecord / EditRecord donde el campo sea `image_id` (FK).
 */
trait HasImageFileUpload
{
    /**
     * Crea un registro en `files` y devuelve su id.
     */
    public function saveImageAsFile(string $disk, string $path, array $extra = []): int
    {
        $fullPath = storage_path('app/public/'.$path);
        $mime = file_exists($fullPath) ? (mime_content_type($fullPath) ?: null) : null;

        $file = File::create(array_merge([
            'user_id' => auth()->id(),
            'storage_disk' => $disk,
            'path' => $path,
            'mime' => $mime,
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
        ], $extra));

        return $file->id;
    }
}
