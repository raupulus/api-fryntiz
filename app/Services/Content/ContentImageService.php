<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentPageFormatEnum;
use App\Exceptions\ContentUploadException;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageRaw;
use App\Models\Content\ContentSeo;
use App\Models\File;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Throwable;

/**
 * Lo que necesita del servidor la pestaña de imágenes de una página (H2 de la
 * auditoría de contenidos del 2026-09-24; la pantalla llega en F8).
 *
 * - Qué imágenes usa una página y en qué otros sitios se usa cada una: un
 *   recorte cambia la imagen en todos ellos, y hay que avisar antes.
 * - Editar título y `alt` sin tocar un byte del fichero.
 * - Recortar o sustituir: mismo fichero y mismas URLs de sus copias pequeñas
 *   (`File::replacePixels()`), así que los bloques no se tocan; las copias sólo
 *   se regeneran cuando cambian los píxeles.
 */
class ContentImageService
{
    public function __construct(
        private readonly ContentPageFormatService $pages,
        private readonly ContentFormatConverter $converter,
    ) {}

    /**
     * Las imágenes de una página: las de sus bloques, en orden, y su portada.
     *
     * @return Collection<int, File>
     */
    public function pageImages(ContentPage $page): Collection
    {
        $ids = [];

        foreach ($this->blocks($page) as $block) {
            $fileId = (int) ($block['data']['file']['file_id'] ?? 0);

            if (($block['type'] ?? null) === 'image' && $fileId > 0) {
                $ids[] = $fileId;
            }
        }

        if ($page->image_id !== null) {
            $ids[] = (int) $page->image_id;
        }

        $ids = array_values(array_unique($ids));
        $files = File::query()->whereIn('id', $ids)->get()->keyBy('id');

        return new Collection(array_values(array_filter(array_map(fn (int $id): ?File => $files->get($id), $ids))));
    }

    /**
     * Los sitios donde se usa un fichero: páginas (por su `file_id` en el JSON
     * de Editor.js), portadas de páginas y de contenidos, imagen SEO y
     * galerías.
     *
     * @return list<array{type: string, label: string, id: int}>
     */
    public function usages(File $file): array
    {
        $usages = [];

        $pageIds = ContentPageRaw::query()
            ->whereRaw('content ~ ?', ['"file_id"\s*:\s*'.$file->id.'([^0-9]|$)'])
            ->pluck('content_page_id')
            ->unique();

        foreach (ContentPage::query()->whereIn('id', $pageIds)->with('contentModel')->get() as $page) {
            $usages[] = ['type' => 'page', 'label' => $this->pageLabel($page), 'id' => $page->id];
        }

        foreach (ContentPage::query()->where('image_id', $file->id)->with('contentModel')->get() as $page) {
            $usages[] = ['type' => 'page-cover', 'label' => 'Portada de '.$this->pageLabel($page), 'id' => $page->id];
        }

        foreach (Content::query()->where('image_id', $file->id)->get() as $content) {
            $usages[] = ['type' => 'content-cover', 'label' => 'Portada de «'.$content->title.'»', 'id' => $content->id];
        }

        foreach (ContentSeo::query()->where('image_id', $file->id)->with('content')->get() as $seo) {
            $usages[] = ['type' => 'seo', 'label' => 'Imagen SEO de «'.($seo->content->title ?? '—').'»', 'id' => (int) $seo->content_id];
        }

        $galleryIds = GalleryImage::query()->where('image_id', $file->id)->pluck('gallery_id')
            ->merge(Gallery::query()->where('image_id', $file->id)->pluck('id'))
            ->unique();

        foreach (Gallery::query()->whereIn('id', $galleryIds)->get() as $gallery) {
            $usages[] = ['type' => 'gallery', 'label' => 'Galería «'.$gallery->name.'»', 'id' => $gallery->id];
        }

        return $usages;
    }

    /**
     * Título y texto alternativo, sin tocar el fichero.
     */
    public function updateTexts(File $file, ?string $title, ?string $alt): void
    {
        $clean = fn (?string $text): ?string => $text === null
            ? null
            : mb_substr(trim((string) preg_replace('/\s+/u', ' ', strip_tags($text))), 0, 511);

        $file->update(array_filter(['title' => $clean($title), 'alt' => $clean($alt)], fn (?string $value): bool => $value !== null));
    }

    /**
     * Recorta la imagen (coordenadas en píxeles del original).
     *
     * @throws InvalidArgumentException si no es una imagen o el recorte se sale.
     */
    public function crop(File $file, int $x, int $y, int $width, int $height): void
    {
        $this->ensureImage($file);

        if ($width < 1 || $height < 1 || $x < 0 || $y < 0
            || $x + $width > (int) $file->width || $y + $height > (int) $file->height) {
            throw new InvalidArgumentException('El recorte se sale de la imagen.');
        }

        $image = File::decodeImage($file->storagePathFile, (string) $file->fileType?->mime);
        $image->crop($width, $height, $x, $y);

        $file->replacePixels($image);
    }

    /**
     * Sustituye la imagen por otra, con los mismos límites que una subida.
     *
     * @throws ContentUploadException
     */
    public function replace(File $file, UploadedFile $upload): void
    {
        $this->ensureImage($file);

        $mime = (string) ($upload->getMimeType() ?: $upload->getClientMimeType());

        if (! File::canConvertToWebp($mime)) {
            throw new ContentUploadException(in_array($mime, File::IMAGICK_ONLY_MIMES, true)
                ? 'Este servidor no puede leer esa foto (HEIC o AVIF): conviértela a JPG.'
                : 'Para sustituir una imagen hace falta otra imagen (JPG, PNG o WebP).');
        }

        if ((int) $upload->getSize() > ContentFileService::MAX_IMAGE_BYTES) {
            throw new ContentUploadException('La imagen pesa más de 20 MB, que es el máximo para imágenes.');
        }

        $file->replacePixels(File::decodeImage((string) $upload->getRealPath(), $mime));
    }

    /**
     * @throws InvalidArgumentException
     */
    private function ensureImage(File $file): void
    {
        if ($file->fileType?->type !== 'image' || ! is_file($file->storagePathFile)) {
            throw new InvalidArgumentException('El fichero no es una imagen o no está en el disco.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(ContentPage $page): array
    {
        try {
            $json = $this->pages->contentIn($page, ContentPageFormatEnum::EditorJs);

            return $json === '' ? [] : $this->converter->decodeBlocks($json);
        } catch (Throwable) {
            return [];
        }
    }

    private function pageLabel(ContentPage $page): string
    {
        return sprintf('«%s», página %d (%s)', $page->contentModel->title ?? '—', (int) $page->order, $page->title ?? '');
    }
}
