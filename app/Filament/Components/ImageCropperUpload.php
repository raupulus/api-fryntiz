<?php

declare(strict_types=1);

namespace App\Filament\Components;

use App\Models\File;
use Filament\Forms\Components\FileUpload;

/**
 * Componente reutilizable de subida de imágenes con cropper preconfigurado.
 *
 * Uso: ImageCropperUpload::makeImage('photo')->avatar()->directory('photos');
 */
class ImageCropperUpload extends FileUpload
{
    /**
     * @param  string  $validationLabel  Nombre legible del campo para los mensajes de
     *                                   validación («El campo :attribute…»). Sin esto, Livewire usa
     *                                   la ruta interna del estado (`data.image_id.<uuid>`), que no
     *                                   dice nada a quien lo lee.
     */
    public static function makeImage(string $name, string $validationLabel = 'imagen'): static
    {
        return static::make($name)
            ->image()
            ->imageEditor()
            ->disk('public')
            ->visibility('public')
            ->maxSize(4096)
            // Explícito y no sólo por el valor por defecto de `multiple()`
            // (`false`): sin esto, nada impide que el campo llegue a tener dos
            // elementos a la vez —el que ya había y uno nuevo— si el navegador
            // añade el segundo antes de que la retirada del primero termine de
            // sincronizarse con el servidor.
            ->maxFiles(1)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->validationAttribute($validationLabel)
            // El mensaje genérico de Laravel para «uploaded» no dice la causa,
            // y casi siempre es que el archivo supera el límite del servidor
            // (`upload_max_filesize`/`post_max_size`), no un fallo aleatorio.
            ->validationMessages([
                'uploaded' => 'No se ha podido subir el archivo: revisa que no supere el tamaño máximo permitido o inténtalo de nuevo.',
            ]);
    }

    /**
     * Para campos que son una clave foránea a `files` (un `*_id`), no una ruta
     * de disco. El estado ya guardado es un id numérico, y sin esto Filament lo
     * trataría como una ruta dentro del disco configurado —nunca la
     * encontraría— y el campo se enseñaba siempre vacío, aunque el registro sí
     * tuviera imagen.
     *
     * `fetchFileInformation(false)` quita esa comprobación contra el disco, y
     * `getUploadedFileUsing()` resuelve la vista previa a mano desde el propio
     * modelo `File`. Con esto el campo enseña, recorta y permite quitar la
     * imagen ya guardada con los controles nativos de Filament, en vez del
     * hueco vacío de siempre.
     */
    public function asFileRecord(): static
    {
        return $this
            ->fetchFileInformation(false)
            ->getUploadedFileUsing(function (mixed $file): ?array {
                if (blank($file)) {
                    return null;
                }

                $record = File::find((int) $file);

                if (! $record) {
                    return null;
                }

                return [
                    'name' => $record->original_name ?? $record->name,
                    'size' => $record->size ?? 0,
                    'type' => $record->fileType?->mime,
                    'url' => $record->url,
                ];
            });
    }

    public function avatar(): static
    {
        return $this
            ->imageEditorAspectRatios(['1:1'])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth('512')
            ->imageResizeTargetHeight('512');
    }

    public function cover16x9(): static
    {
        return $this
            ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth('1600')
            ->imageResizeTargetHeight('900');
    }

    public function logo(): static
    {
        return $this
            ->imageEditorAspectRatios(['1:1', '3:1'])
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth('512')
            ->imageResizeTargetHeight('512');
    }

    public function icon(int $size = 64): static
    {
        return $this
            ->imageEditorAspectRatios(['1:1'])
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth((string) $size)
            ->imageResizeTargetHeight((string) $size)
            ->maxSize(512);
    }
}
