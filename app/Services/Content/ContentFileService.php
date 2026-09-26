<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Exceptions\ContentUploadException;
use App\Helpers\TextFormatParseHelper;
use App\Models\Content\Content;
use App\Models\Content\ContentFile;
use App\Models\File;
use App\Services\Http\PublicUrlFetcher;
use Illuminate\Http\UploadedFile;

/**
 * Ficheros que se suben desde el editor de un contenido (C1, C5 y B5 de la
 * auditoría de contenidos del 2026-09-24).
 *
 * - Cada fichero queda vinculado al contenido (`content_files`), como en `main`.
 * - Las imágenes se guardan en WebP a calidad 85, sin metadatos, giradas y a
 *   2560 px como mucho; las fotos HEIC y AVIF se abren con Imagick.
 * - Límites: 20 MB las imágenes (D12) y 50 MB el resto, con el motivo.
 * - Los PDF, tal cual, con sus metadatos (DUDA-5), y cualquier otro tipo
 *   también tal cual (D13).
 * - La respuesta lleva las mismas claves que la de `main`, que es lo que ya
 *   guardan los bloques de las páginas publicadas.
 */
class ContentFileService
{
    public const MAX_IMAGE_BYTES = 20 * 1024 * 1024;

    public const MAX_OTHER_BYTES = 50 * 1024 * 1024;

    /**
     * Directorio (y módulo) de los ficheros del editor, el mismo que en `main`.
     */
    public const MODULE = 'content';

    public function __construct(private readonly PublicUrlFetcher $fetcher) {}

    /**
     * @return array<string, mixed> Los datos del fichero para el bloque de Editor.js.
     *
     * @throws ContentUploadException
     */
    public function store(Content $content, UploadedFile $upload): array
    {
        $mime = (string) ($upload->getMimeType() ?: $upload->getClientMimeType());
        $size = (int) $upload->getSize();
        $isImage = str_starts_with($mime, 'image/');

        if ($isImage && $size > self::MAX_IMAGE_BYTES) {
            throw new ContentUploadException(sprintf('La imagen pesa %s y el máximo para imágenes es 20 MB.', $this->readable($size)));
        }

        if (! $isImage && $size > self::MAX_OTHER_BYTES) {
            throw new ContentUploadException(sprintf('El fichero pesa %s y el máximo es 50 MB.', $this->readable($size)));
        }

        if (in_array($mime, File::IMAGICK_ONLY_MIMES, true) && ! $this->canRead($mime)) {
            throw new ContentUploadException($mime === 'image/avif'
                ? 'Este servidor no puede leer imágenes AVIF: conviértela a JPG o PNG.'
                : 'Este servidor no puede leer fotos HEIC: conviértela a JPG.');
        }

        $file = File::addFile($upload, self::MODULE, false, validate: false, webpOriginal: true);

        if (! $file) {
            throw new ContentUploadException('No se ha podido guardar el fichero.');
        }

        $contentFile = ContentFile::query()->firstOrCreate(['content_id' => $content->id, 'file_id' => $file->id]);

        return $this->payload($content, $file->refresh(), $contentFile);
    }

    /**
     * Descarga una imagen pegada por URL y la guarda como si se hubiera subido.
     *
     * @return array<string, mixed>
     *
     * @throws ContentUploadException
     */
    public function storeFromUrl(Content $content, string $url): array
    {
        $response = $this->fetcher->fetch(trim($url), self::MAX_IMAGE_BYTES, ['Accept' => 'image/*'], timeout: 20);

        if ($response === null) {
            throw new ContentUploadException('No se puede descargar esa dirección: tiene que ser una imagen pública, con http o https.');
        }

        if (! $response->successful()) {
            throw new ContentUploadException("La dirección ha respondido con un error ({$response->status}): no se ha descargado nada.");
        }

        if (! $response->complete) {
            throw new ContentUploadException('La imagen pesa más de 20 MB, que es el máximo para imágenes.');
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'editor-url-');
        file_put_contents($path, $response->body);

        try {
            $mime = (string) mime_content_type($path);

            if (! str_starts_with($mime, 'image/')) {
                throw new ContentUploadException('Esa dirección no es una imagen.');
            }

            $name = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'imagen';

            return $this->store($content, new UploadedFile($path, $name, $mime, null, true));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Los datos de un fichero con las claves que devolvía `main`.
     *
     * Para una imagen, `url` es la copia de 640 px, `url_thumbnail` la de 160 y
     * `url_large` la de 1280 (si la imagen es más pequeña, la mayor que haya).
     * Para el resto, el propio fichero.
     *
     * @return array<string, mixed>
     */
    public function payload(Content $content, File $file, ContentFile $contentFile): array
    {
        $image = $file->fileType?->type === 'image';
        $url = $image ? $file->thumbnail('normal') : $file->url;
        $thumbnail = $image ? $file->thumbnail('small') : $file->url;
        $large = $image ? $file->thumbnail('large') : $file->url;
        $relative = fn (string $url): string => ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return [
            'url' => $url,
            'path' => $relative($url),
            'url_thumbnail' => $thumbnail,
            'path-thumbnail' => $relative($thumbnail),
            'url_large' => $large,
            'path-large' => $relative($large),
            'content_id' => $content->id,
            'content_file_id' => $contentFile->id,
            'file_id' => $file->id,
            'module' => $file->module,
            'title' => $file->title,
            'alt' => $file->alt,
            'name' => $file->name,
            'size' => $file->size,
            'extension' => $file->fileType?->extension,
            'mime' => $file->fileType?->mime,
            'file_type_image' => $file->fileType?->url_image,
        ];
    }

    /**
     * ¿Puede este servidor abrir una imagen de este tipo (HEIC, AVIF)? Aparte
     * para poder simular en los tests un servidor sin Imagick.
     */
    protected function canRead(string $mime): bool
    {
        return File::canReadWithImagick($mime);
    }

    private function readable(int $bytes): string
    {
        return str_replace('.', ',', TextFormatParseHelper::formatBytes($bytes, 1));
    }
}
