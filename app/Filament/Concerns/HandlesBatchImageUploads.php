<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Trait para resolver y persistir lotes de imágenes subidas en galerías.
 *
 * Resuelve de manera tolerante archivos temporales de Livewire tanto si vienen
 * como instancias de UploadedFile como si son strings de rutas temporales en disco,
 * evitando caídas abruptas y garantizando que un fallo puntual no descarte las demás imágenes.
 */
trait HandlesBatchImageUploads
{
    /**
     * Resuelve un upload de Livewire o ruta de archivo temporal a un objeto UploadedFile válido.
     */
    protected function resolveUploadedFile(mixed $uploaded): ?UploadedFile
    {
        if ($uploaded instanceof UploadedFile) {
            return $uploaded;
        }

        if (is_string($uploaded) && trim($uploaded) !== '') {
            $livewireDisk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default', 'local');
            $candidatePaths = [
                $uploaded,
                Storage::disk($livewireDisk)->path($uploaded),
                Storage::disk('public')->path($uploaded),
                storage_path('app/'.$uploaded),
                storage_path('app/private/'.$uploaded),
                storage_path('app/public/'.$uploaded),
            ];

            foreach ($candidatePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $mime = @mime_content_type($path) ?: 'image/jpeg';

                    return new UploadedFile(
                        $path,
                        basename($path),
                        $mime,
                        null,
                        true
                    );
                }
            }
        }

        return null;
    }

    /**
     * Procesa un lote de imágenes y las vincula ordenadamente a la galería.
     *
     * @param  array<int, mixed>  $images  Lista de UploadedFiles o paths temporales.
     * @param  Gallery  $gallery  Instancia de la galería destino.
     * @param  int  $startOrder  Número de orden inicial para las nuevas imágenes.
     * @return array{success: int, failed: int}
     */
    protected function attachBatchImagesToGallery(array $images, Gallery $gallery, int $startOrder = 1): array
    {
        $successCount = 0;
        $failedCount = 0;
        $firstFileId = null;
        $order = $startOrder;

        foreach ($images as $uploaded) {
            try {
                $uploadedFile = $this->resolveUploadedFile($uploaded);

                if (! $uploadedFile) {
                    Log::warning('Gallery: no se pudo resolver el archivo temporal para la galería', [
                        'gallery_id' => $gallery->id,
                        'uploaded' => is_string($uploaded) ? $uploaded : get_debug_type($uploaded),
                    ]);
                    $failedCount++;

                    continue;
                }

                $file = File::addFile($uploadedFile, 'galleries', is_private: false);

                if (! $file) {
                    Log::warning('Gallery: File::addFile rechazó el archivo para la galería', [
                        'gallery_id' => $gallery->id,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'size' => $uploadedFile->getSize(),
                        'mime' => $uploadedFile->getMimeType(),
                    ]);
                    $failedCount++;

                    continue;
                }

                GalleryImage::create([
                    'gallery_id' => $gallery->id,
                    'image_id' => $file->id,
                    'order' => $order++,
                ]);

                if ($firstFileId === null) {
                    $firstFileId = $file->id;
                }

                $successCount++;
            } catch (\Throwable $e) {
                Log::error('Gallery: excepción al procesar imagen en lote', [
                    'gallery_id' => $gallery->id,
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        // Si la galería no tenía portada definida, asignar la primera foto subida con éxito
        if (empty($gallery->image_id) && $firstFileId) {
            $gallery->update(['image_id' => $firstFileId]);
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount,
        ];
    }
}
