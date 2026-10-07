<?php

declare(strict_types=1);

namespace App\Console\Commands\Files;

use App\Models\File;
use App\Models\FileType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\WebpEncoder;
use Throwable;

class ConvertImagesToWebpCommand extends Command
{
    protected $signature = 'files:convert-to-webp {--dry-run : Simular la conversión sin modificar ficheros ni registros en la base de datos}';

    protected $description = 'Convierte imágenes originales existentes (JPEG, PNG, BMP, etc.) a WebP (calidad 85, máx. 2560 px en su lado mayor) y regenera miniaturas.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo DRY-RUN activo: no se modificarán ficheros ni la base de datos.');
        }

        $this->info('Buscando imágenes convertibles en la base de datos...');

        $files = File::query()
            ->whereHas('fileType', function ($query): void {
                $query->where('type', 'image')->where('mime', '!=', 'image/webp');
            })
            ->with(['fileType', 'thumbnails'])
            ->get();

        $totalFiles = $files->count();
        $this->info("Imágenes encontradas pendientes de revisión: {$totalFiles}");

        $convertedCount = 0;
        $skippedCount = 0;
        $missingCount = 0;
        $errorCount = 0;
        $bytesSaved = 0;

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        /** @var File $file */
        foreach ($files as $file) {
            $mime = (string) $file->fileType?->mime;

            if (! File::canConvertToWebp($mime)) {
                $skippedCount++;
                $bar->advance();

                continue;
            }

            $sourcePath = $file->storagePathFile;

            if ($sourcePath === '' || ! file_exists($sourcePath)) {
                $missingCount++;
                $bar->advance();

                continue;
            }

            $oldSize = $file->size ?: (@filesize($sourcePath) ?: 0);

            if ($dryRun) {
                $convertedCount++;
                $bar->advance();

                continue;
            }

            try {
                $image = File::decodeImage($sourcePath, $mime);
                $image->scaleDown(width: File::MAX_IMAGE_WIDTH, height: File::MAX_IMAGE_WIDTH);
                File::stripMetadata($image);

                $targetPath = (string) preg_replace('/\.[^.\/]*$/', '', $sourcePath).'.webp';
                $targetName = (string) preg_replace('/\.[^.\/]*$/', '', (string) $file->name).'.webp';

                $image->encode(new WebpEncoder(quality: File::WEBP_QUALITY, strip: true))->save($targetPath);

                if ($targetPath !== $sourcePath) {
                    @unlink($sourcePath);
                }

                clearstatcache(true, $targetPath);
                [$newWidth, $newHeight] = @getimagesize($targetPath) ?: [null, null];
                $newSize = @filesize($targetPath) ?: null;

                $webpFileType = FileType::addFileType('image/webp', 'webp');

                $file->update([
                    'name' => $targetName,
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'size' => $newSize,
                    'file_type_id' => $webpFileType?->id,
                ]);

                $file->regenerateThumbnailsInPlace();

                if ($newSize !== null && $oldSize > $newSize) {
                    $bytesSaved += ($oldSize - $newSize);
                }

                $convertedCount++;
            } catch (Throwable $e) {
                $errorCount++;
                Log::error('files:convert-to-webp: error al convertir archivo', [
                    'file_id' => $file->id,
                    'path' => $sourcePath,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Migrar avatares de usuarios (profile_photo_path)
        $this->info('Revisando fotos de perfil de usuarios...');
        $users = User::query()
            ->whereNotNull('profile_photo_path')
            ->where('profile_photo_path', 'not like', '%.webp')
            ->get();

        $userAvatarsConverted = 0;

        /** @var User $user */
        foreach ($users as $user) {
            $avatarPath = Storage::disk('public')->path((string) $user->profile_photo_path);

            if (file_exists($avatarPath)) {
                if (! $dryRun) {
                    $user->convertProfilePhotoToWebp();
                    $user->saveQuietly();
                }
                $userAvatarsConverted++;
            }
        }

        $this->info('Proceso completado.');

        $this->table(
            ['Métrica', 'Total'],
            [
                ['Imágenes analizadas', (string) $totalFiles],
                ['Imágenes convertidas a WebP', (string) $convertedCount],
                ['Imágenes omitidas (no convertibles)', (string) $skippedCount],
                ['Ficheros no encontrados en disco', (string) $missingCount],
                ['Errores de conversión', (string) $errorCount],
                ['Fotos de perfil de usuario convertidas', (string) $userAvatarsConverted],
                ['Espacio aproximado ahorrado', $this->formatBytes($bytesSaved)],
            ]
        );

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return sprintf('%.2f %s', $bytes / (1024 ** $power), $units[$power]);
    }
}
