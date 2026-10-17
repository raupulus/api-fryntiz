<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\Content;
use App\Models\File;
use App\Models\Platform;
use App\Services\Ai\GeneratedImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Portadas con IA (la de la entrada y la de cada página): el prompt que se le
 * manda al proveedor y las imágenes que vuelven.
 *
 * Cada imagen que llega se recorta al centro a 16:9, como el campo de portada
 * (`ImageCropperUpload::cover16x9()`), y se guarda como **subida temporal de
 * Livewire**, sólo para enseñarla. La elegida la descarga el navegador y la
 * mete en el campo como una subida más: la guarda el camino de siempre
 * (`File::addFile()`, en WebP, D40). Las que no se eligen las borra Livewire a
 * las 24 h (D53).
 *
 * La imagen que se manda a mejorar no llega del navegador: al abrir el modal
 * se apunta en la caché, ligada a esa persona y ese contenido, y el navegador
 * sólo ve un token.
 */
class ContentAiImageService
{
    public const MAX_IMAGES = 5;

    /**
     * Lo más grande que se guarda, como el resto de imágenes (D40).
     */
    private const MAX_WIDTH = 2560;

    /**
     * La imagen que se manda a mejorar: no hace falta más.
     */
    private const SOURCE_MAX_SIDE = 2048;

    private const TOKEN_HOURS = 24;

    /**
     * La instrucción que se le manda al proveedor. Quien redacta sólo ve lo que
     * escribe: el contexto, las reglas y los colores van aquí.
     */
    public function buildPrompt(Content $content, ?string $pageTitle, string $userPrompt, bool $useColors, bool $improve): string
    {
        $platform = $content->platform;
        $lines = [];

        $lines[] = $improve
            ? 'Mejora esta imagen, que es la portada de un artículo de una web. Mantén lo que muestra y su composición salvo que la petición diga otra cosa.'
            : 'Crea la imagen de portada de un artículo de una web.';

        $lines[] = 'Web: '.($platform instanceof Platform ? $platform->title : 'General').'. Artículo: «'.$content->title.'».'
            .(filled($pageTitle) ? ' Apartado: «'.$pageTitle.'».' : '')
            .(filled($content->excerpt) ? ' De qué trata: '.Str::limit(trim((string) $content->excerpt), 400) : '');

        $lines[] = filled(trim($userPrompt))
            ? 'Petición: '.trim($userPrompt)
            : ($improve ? 'Petición: mejora la calidad, la nitidez y la luz.' : 'Petición: ilustra el tema del artículo.');

        $lines[] = 'Formato apaisado 16:9: deja el motivo principal en el centro, porque los bordes pueden recortarse.';
        $lines[] = 'Sin texto, letras, números ni logotipos dentro de la imagen, salvo que la petición los pida.';
        $lines[] = 'Que no parezca una imagen genérica hecha con IA: nada de brillos, destellos ni efectos sobrecargados.';

        $colors = $useColors && $platform instanceof Platform ? $platform->themeColors() : [];

        if ($colors !== []) {
            $lines[] = 'La web usa estos colores: '.implode(', ', array_map(
                fn (array $color): string => $color['hex'].($color['role'] !== null ? ' ('.mb_strtolower($color['role']->label()).')' : ''),
                $colors,
            )).'. Basa la paleta de la imagen en ellos para que se integre en la web.'
                .(collect($colors)->contains(fn (array $color): bool => $color['role']?->value === 'background')
                    ? ' Se verá sobre el color de fondo: cuida el contraste con él.'
                    : '');
        }

        return implode("\n", $lines);
    }

    /**
     * La imagen recortada al centro a 16:9, en JPEG, sin pasar de 2560 px de
     * ancho. Si no se puede leer, tal cual.
     */
    public function toCover(GeneratedImage $image): GeneratedImage
    {
        try {
            $width = min($image->width, self::MAX_WIDTH, (int) floor($image->height * 16 / 9));
            $height = (int) round($width * 9 / 16);

            $bytes = (string) Image::decodeBinary($image->bytes)->cover($width, $height)->encode(new JpegEncoder(quality: 92));

            return GeneratedImage::fromBytes($bytes) ?? $image;
        } catch (Throwable) {
            return $image;
        }
    }

    /**
     * Guarda una imagen como subida temporal de Livewire y devuelve la
     * dirección firmada de su vista previa. Al elegirla, el navegador la
     * descarga de ahí y la mete en el campo de portada como una subida más.
     *
     * @return array{url: string, width: int, height: int}
     */
    public function storeCandidate(GeneratedImage $image): array
    {
        $filename = Str::random(40).'.'.$image->extension();
        $storage = FileUploadConfiguration::storage();

        $storage->put(FileUploadConfiguration::path($filename), $image->bytes);
        $storage->put(FileUploadConfiguration::path($filename.'.json'), (string) json_encode([
            'name' => 'portada-ia.'.$image->extension(),
            'type' => $image->mime,
            'size' => strlen($image->bytes),
            'hash' => $filename,
        ]));

        return [
            'url' => TemporaryUploadedFile::createFromLivewire($filename)->temporaryUrl(),
            'width' => $image->width,
            'height' => $image->height,
        ];
    }

    /**
     * Un token para la imagen que se va a mejorar: la portada guardada (un
     * `File`) o la que está pendiente en el campo (una subida temporal). Así el
     * controlador nunca recibe una ruta ni un id del navegador.
     */
    public function registerSource(TemporaryUploadedFile|File $source, Content $content, int $userId): string
    {
        return $this->remember(
            $source instanceof File
                ? ['type' => 'file', 'id' => $source->id]
                : ['type' => 'upload', 'filename' => $source->getFilename()],
            $content,
            $userId,
        );
    }

    /**
     * La imagen de un token, lista para mandarla a mejorar: en JPEG y con el
     * lado mayor en 2048 px como mucho.
     */
    public function source(string $token, Content $content, int $userId): ?GeneratedImage
    {
        $entry = $this->entry($token, $content, $userId);

        if ($entry === null) {
            return null;
        }

        $bytes = match ($entry['type']) {
            'upload' => FileUploadConfiguration::storage()->get(FileUploadConfiguration::path($entry['filename'])),
            'file' => $this->fileBytes((int) $entry['id']),
            default => null,
        };

        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        try {
            $bytes = (string) Image::decodeBinary($bytes)->scaleDown(self::SOURCE_MAX_SIDE, self::SOURCE_MAX_SIDE)->encode(new JpegEncoder(quality: 90));
        } catch (Throwable) {
            return null;
        }

        return GeneratedImage::fromBytes($bytes);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function remember(array $entry, Content $content, int $userId): string
    {
        $token = Str::random(40);

        Cache::put($this->key($token), [...$entry, 'content_id' => $content->id, 'user_id' => $userId], now()->addHours(self::TOKEN_HOURS));

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function entry(string $token, Content $content, int $userId): ?array
    {
        if (preg_match('/^[A-Za-z0-9]{40}$/', $token) !== 1) {
            return null;
        }

        $entry = Cache::get($this->key($token));

        return is_array($entry) && (int) ($entry['content_id'] ?? 0) === (int) $content->id && (int) ($entry['user_id'] ?? 0) === $userId
            ? $entry
            : null;
    }

    private function fileBytes(int $fileId): ?string
    {
        $path = File::query()->find($fileId)?->storage_path_file;

        return filled($path) && is_file((string) $path) ? (string) file_get_contents((string) $path) : null;
    }

    private function key(string $token): string
    {
        return 'content-ai-image:'.$token;
    }
}
