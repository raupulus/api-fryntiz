<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentPageVersionReasonEnum;
use App\Exceptions\ContentPageConflictException;
use App\Exceptions\ContentPageLockedException;
use App\Models\Content\ContentAvailablePageRaw;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageDraft;
use App\Models\Content\ContentPageRaw;
use App\Models\Content\ContentPageVersion;
use App\Models\File;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Fuente única de una página y las versiones que se derivan de ella.
 *
 * Cada página se escribe en UN formato, su fuente, que marca
 * `content_pages.current_page_raw_id`. Al guardar:
 *
 *  - la fuente se guarda en `content_page_raw` con su tipo;
 *  - `content_pages.content` se regenera con el HTML que se sirve a la web;
 *  - Editor.js y Markdown, si no son la fuente, se regeneran a partir de ella
 *    (el HTML no se guarda aparte: es `content`).
 *
 * Si el guardado cambia el contenido, lo que había antes pasa al historial de
 * versiones (`ContentPageHistoryService`, G5), con el motivo: guardado, cambio
 * de formato, recuperación de una versión o de un borrador, o página vaciada.
 * Antes se guardaba sólo al cambiar de formato, como fila borrada de
 * `content_page_raw`.
 *
 * `savePage()`, además (F6 del plan de contenidos del 2026-09-24):
 *  - no guarda si otro tiene la página bloqueada (`ContentPageLockService`);
 *  - no guarda si la página se ha guardado desde otro sitio después de abrirla
 *    (D4: la fecha con la que se abrió no coincide con `updated_at`);
 *  - borra el borrador de quien guarda;
 *  - marca y desmarca los ficheros del contenido que ya no se usan (C2).
 *
 * Páginas sin `current_page_raw_id` (anteriores a esto, o nuevas sin contenido):
 * si tienen JSON de Editor.js, la fuente es Editor.js; si sólo tienen HTML en
 * `content`, es HTML; y si están vacías, Editor.js, que es el formato por
 * defecto. No hace falta migrar nada: la próxima vez que se guarden quedan
 * marcadas.
 */
class ContentPageFormatService
{
    public function __construct(
        private readonly ContentFormatConverter $converter,
        private readonly ContentBlockValidator $validator,
        private readonly ContentHtmlSanitizer $sanitizer,
        private readonly ContentMarkdownSanitizer $markdownSanitizer,
        private readonly ContentPageHistoryService $history,
        private readonly ContentPageLockService $locks,
        private readonly ContentFileUsageService $usage,
    ) {}

    /**
     * Formato en el que se edita la página.
     */
    public function sourceFormat(ContentPage $page): ContentPageFormatEnum
    {
        $page->loadMissing(['currentRawType', 'raws.availableType']);

        $marked = $page->currentRawType?->type;
        $format = $marked !== null ? ContentPageFormatEnum::fromRawType($marked) : null;

        if ($format !== null) {
            return $format;
        }

        if ($this->rawFor($page, ContentPageFormatEnum::EditorJs) !== null) {
            return ContentPageFormatEnum::EditorJs;
        }

        return filled($page->content) ? ContentPageFormatEnum::Html : ContentPageFormatEnum::EditorJs;
    }

    /**
     * Contenido de la fuente, tal y como se edita.
     */
    public function sourceContent(ContentPage $page): string
    {
        $format = $this->sourceFormat($page);
        $raw = $this->rawFor($page, $format);

        if ($raw !== null) {
            return (string) $raw->content;
        }

        return $format === ContentPageFormatEnum::Html ? (string) $page->content : '';
    }

    /**
     * Contenido de la página en el formato pedido (lo que sirve la API).
     *
     * Si falta la versión derivada (páginas guardadas antes de esto) se
     * convierte al vuelo desde la fuente, sin guardarla.
     */
    public function contentIn(ContentPage $page, ContentPageFormatEnum $format): string
    {
        if ($format === ContentPageFormatEnum::Html) {
            return (string) $page->content;
        }

        $source = $this->sourceFormat($page);

        if ($format === $source) {
            return $this->sourceContent($page);
        }

        $raw = $this->rawFor($page, $format);

        if ($raw !== null) {
            return (string) $raw->content;
        }

        $content = $this->sourceContent($page);

        return $content === '' ? '' : $this->converter->convert($content, $source, $format)->content;
    }

    /**
     * Guarda los datos de la página (título, slug, orden, imagen…) y su
     * contenido **juntos**: si algo falla, no se guarda nada (A2 de la
     * auditoría de contenidos del 2026-09-24). Antes se guardaban por separado
     * y un contenido que fallaba dejaba el título cambiado y el contenido viejo.
     *
     * @param  array<string, mixed>  $attributes  Columnas de `content_pages`.
     * @param  ContentPageVersionReasonEnum|null  $reason  Motivo de la versión que se guarde (recuperar una versión o un borrador); si no, se deduce.
     * @param  User|null  $author  Quién guarda; null es el sistema (se le trata como administrador).
     * @param  CarbonInterface|null  $openedAt  `updated_at` que tenía la página al abrirla; si ha cambiado, no se guarda (D4).
     * @param  string|null  $lockToken  Pestaña que guarda, si tiene el bloqueo.
     * @return bool Si se ha guardado el contenido (false si venía vacío).
     *
     * @throws ValidationException si el contenido no se puede guardar tal cual (ver `save()`).
     * @throws ContentPageLockedException si otro tiene la página bloqueada.
     * @throws ContentPageConflictException si la página se ha guardado desde otro sitio después de abrirla.
     * @throws RuntimeException si faltan los tipos en `content_available_page_raw`.
     */
    public function savePage(
        ContentPage $page,
        array $attributes,
        ContentPageFormatEnum $format,
        ?string $content,
        ?ContentPageVersionReasonEnum $reason = null,
        ?User $author = null,
        ?CarbonInterface $openedAt = null,
        ?string $lockToken = null,
    ): bool {
        $saved = DB::transaction(function () use ($page, $attributes, $format, $content, $reason, $author, $openedAt, $lockToken): bool {
            if ($page->exists) {
                $this->guard($page, $author, $openedAt, $lockToken);
            }

            $previousTitle = $page->exists ? $page->getOriginal('title') : null;
            $page->fill($attributes)->save();

            $saved = $this->persist($page, $format, $content, $reason, $author, $previousTitle);
            $this->forgetDraft($page, $author);

            return $saved;
        });

        $this->usage->refresh($page->content_id);

        return $saved;
    }

    /**
     * Nadie más tiene el bloqueo y la página sigue como cuando se abrió. La
     * fila se lee con `FOR UPDATE`: dos guardados a la vez no pasan los dos.
     *
     * @throws ContentPageLockedException
     * @throws ContentPageConflictException
     */
    private function guard(ContentPage $page, ?User $author, ?CarbonInterface $openedAt, ?string $lockToken): void
    {
        $current = DB::table('content_pages')->where('id', $page->id)->lockForUpdate()->value('updated_at');

        $this->locks->assertCanSave($page, $author, $lockToken);

        if ($openedAt !== null && $current !== null
            && Carbon::parse($current)->format('Y-m-d H:i:s') !== Carbon::instance($openedAt)->utc()->format('Y-m-d H:i:s')) {
            throw new ContentPageConflictException;
        }
    }

    /**
     * Al guardar, el borrador de quien guarda ya no hace falta.
     */
    private function forgetDraft(ContentPage $page, ?User $author): void
    {
        if ($author === null) {
            return;
        }

        ContentPageDraft::query()
            ->where('user_id', $author->id)
            ->where(fn ($query) => $query
                ->where('content_page_id', $page->id)
                ->orWhere(fn ($new) => $new->whereNull('content_page_id')->where('content_id', $page->content_id)))
            ->delete();
    }

    /**
     * Guarda la fuente de la página y regenera todo lo que sale de ella.
     * (Sin comprobar bloqueo ni fecha de apertura: eso es `savePage()`.)
     *
     * Antes de escribir nada:
     *  - Editor.js: cada bloque tiene que traer su dato principal
     *    (`ContentBlockValidator`), y el HTML de sus textos se limpia
     *    (`ContentHtmlSanitizer`), sea quien sea quien guarde;
     *  - si quien guarda no es administrador, no puede añadir ni cambiar bloques
     *    de HTML libre, ni pasar la página a HTML o cambiar su HTML, y el HTML
     *    que escriba dentro de un Markdown se limpia.
     *
     * Si `$content` está vacío no se toca nada (vaciar un editor por error no
     * borra la página), y devuelve false.
     *
     * @param  ContentPageVersionReasonEnum|null  $reason  Motivo de la versión que se guarde; si no, se deduce.
     * @param  User|null  $author  Quién guarda; null es el sistema (se le trata como administrador).
     *
     * @throws ValidationException si el contenido no se puede guardar tal cual, con un mensaje por problema.
     * @throws InvalidArgumentException si el contenido no es válido para su formato.
     * @throws RuntimeException si faltan los tipos en `content_available_page_raw`.
     */
    public function save(ContentPage $page, ContentPageFormatEnum $format, ?string $content, ?ContentPageVersionReasonEnum $reason = null, ?User $author = null): bool
    {
        $saved = $this->persist($page, $format, $content, $reason, $author, $page->title);

        if ($saved) {
            $this->usage->refresh($page->content_id);
        }

        return $saved;
    }

    /**
     * @throws ValidationException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function persist(
        ContentPage $page,
        ContentPageFormatEnum $format,
        ?string $content,
        ?ContentPageVersionReasonEnum $reason,
        ?User $author,
        ?string $previousTitle,
    ): bool {
        if ($content === null || trim($content) === '') {
            return false;
        }

        $content = $this->prepare($page, $format, $content, $author === null || $author->isAdmin());
        $types = $this->typeIds();
        $previousBlocks = $format === ContentPageFormatEnum::EditorJs ? $this->editorJsBlocks($page) : [];

        // Primero lo que puede fallar, antes de escribir nada.
        $served = $this->converter->toServedHtml($content, $format);

        DB::transaction(function () use ($page, $format, $content, $reason, $author, $previousTitle, $types, $served, $previousBlocks): void {
            $page->unsetRelation('raws')->unsetRelation('currentRawType');

            $previous = $this->sourceFormat($page);
            $previousContent = $this->sourceContent($page);

            // Lo que había pasa al historial si cambia algo del contenido.
            if (trim($previousContent) !== ''
                && ContentPageHistoryService::hash($previous, $previousContent) !== ContentPageHistoryService::hash($format, $content)) {
                $this->history->record($page, $previous, $previousContent, $previousTitle, $reason ?? match (true) {
                    $previous !== $format => ContentPageVersionReasonEnum::FormatChange,
                    trim(strip_tags($served, '<img><iframe>')) === '' => ContentPageVersionReasonEnum::Emptied,
                    default => ContentPageVersionReasonEnum::Save,
                }, $author);
            }

            $this->putRaw($page, $format, $content, $types);

            if ($format === ContentPageFormatEnum::EditorJs) {
                $this->syncFileTexts($page, $previousBlocks, $this->converter->decodeBlocks($content));
            }

            $page->fill([
                'current_page_raw_id' => $types[$format->rawType()],
                'content' => $served,
            ])->save();

            foreach ([ContentPageFormatEnum::EditorJs, ContentPageFormatEnum::Markdown] as $derived) {
                if ($derived === $format) {
                    continue;
                }

                try {
                    $converted = $this->converter->convert($content, $format, $derived)->content;

                    if ($derived === ContentPageFormatEnum::EditorJs) {
                        $converted = $this->converter->encodeBlocks($this->converter->decodeBlocks($converted));
                    }

                    $this->putRaw($page, $derived, $converted, $types);
                } catch (Throwable $e) {
                    // Una versión derivada que no sale no puede impedir guardar
                    // la fuente: se avisa en el log y la API la convierte al vuelo.
                    report($e);
                }
            }
        });

        $page->unsetRelation('raws')->unsetRelation('currentRawType');

        return true;
    }

    /**
     * Problemas que impiden guardar este contenido, sin guardar nada. Es lo
     * que enseña el formulario del panel antes de guardar.
     *
     * @return list<string>
     */
    public function problems(?ContentPage $page, ContentPageFormatEnum $format, ?string $content, ?User $author = null): array
    {
        if ($content === null || trim($content) === '') {
            return [];
        }

        try {
            $this->prepare($page ?? new ContentPage, $format, $content, $author === null || $author->isAdmin());
        } catch (ValidationException $e) {
            return array_values(array_merge(...array_values($e->errors())));
        }

        return [];
    }

    /**
     * El contenido listo para guardar: validado y limpio.
     *
     * @throws ValidationException
     */
    private function prepare(ContentPage $page, ContentPageFormatEnum $format, string $content, bool $trusted): string
    {
        return match ($format) {
            ContentPageFormatEnum::EditorJs => $this->prepareBlocks($page, $content, $trusted),
            ContentPageFormatEnum::Markdown => $trusted ? $content : $this->markdownSanitizer->sanitize($content, $this->storedRawHtml($page)),
            ContentPageFormatEnum::Html => $trusted ? $content : $this->refuseHtml($page, $content),
        };
    }

    /**
     * @throws ValidationException
     */
    private function prepareBlocks(ContentPage $page, string $content, bool $trusted): string
    {
        $document = json_decode($content, true);

        if (! is_array($document) || ! is_array($document['blocks'] ?? null)) {
            throw ValidationException::withMessages(['content' => 'Esto no es un JSON de Editor.js: falta la clave «blocks».']);
        }

        $blocks = array_values($document['blocks']);
        $errors = $this->validator->errors($blocks);

        if (! $trusted) {
            $allowed = $this->storedRawHtml($page);

            foreach ($blocks as $index => $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'raw' && ! in_array($block['data']['html'] ?? null, $allowed, true)) {
                    $errors[] = sprintf('Bloque %d (HTML): sólo un administrador puede añadir o cambiar bloques de HTML libre.', $index + 1);
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['content' => $errors]);
        }

        $clean = $this->sanitizer->blocks($blocks);

        // Lo que ya estaba limpio se guarda tal y como llegó.
        if ($clean === $blocks) {
            return $content;
        }

        $document['blocks'] = $clean;

        return (string) json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Pie de foto (o título del adjunto) → título y texto alternativo del
     * fichero (C3 de la auditoría de contenidos), **sólo si ha cambiado desde
     * el último guardado**: así se respeta un `alt` escrito a mano desde la
     * pestaña de imágenes mientras nadie toque el pie. Sólo para ficheros
     * vinculados a este contenido: un `file_id` en el JSON no basta para tocar
     * un fichero ajeno.
     *
     * @param  list<array<string, mixed>>  $before
     * @param  list<array<string, mixed>>  $after
     */
    private function syncFileTexts(ContentPage $page, array $before, array $after): void
    {
        $texts = function (array $blocks): array {
            $texts = [];

            foreach ($blocks as $block) {
                $type = $block['type'] ?? null;
                $fileId = (int) ($block['data']['file']['file_id'] ?? 0);

                if ($fileId > 0 && in_array($type, ['image', 'attaches'], true)) {
                    $text = $type === 'image' ? ($block['data']['caption'] ?? '') : ($block['data']['title'] ?? '');
                    $texts[$fileId] = mb_substr(trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text)))), 0, 511);
                }
            }

            return $texts;
        };

        $old = $texts($before);
        $changed = array_filter(
            $texts($after),
            fn (string $text, int $fileId): bool => $text !== '' && ($old[$fileId] ?? null) !== $text,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($changed === [] || $page->content_id === null) {
            return;
        }

        $ours = ContentFile::query()
            ->where('content_id', $page->content_id)
            ->whereIn('file_id', array_keys($changed))
            ->pluck('file_id');

        foreach (File::query()->whereIn('id', $ours)->get() as $file) {
            $file->update(['title' => $changed[$file->id], 'alt' => $changed[$file->id]]);
        }
    }

    /**
     * Bloques de la fuente de la página si está en Editor.js; si no, ninguno.
     *
     * @return list<array<string, mixed>>
     */
    private function editorJsBlocks(ContentPage $page): array
    {
        if (! $page->exists || $this->sourceFormat($page) !== ContentPageFormatEnum::EditorJs) {
            return [];
        }

        try {
            return $this->converter->decodeBlocks($this->sourceContent($page));
        } catch (InvalidArgumentException) {
            return [];
        }
    }

    /**
     * Quien no es administrador no pasa una página a HTML ni cambia su HTML:
     * el HTML se sirve tal cual.
     *
     * @throws ValidationException
     */
    private function refuseHtml(ContentPage $page, string $content): string
    {
        if (! $page->exists || $this->sourceFormat($page) !== ContentPageFormatEnum::Html) {
            throw ValidationException::withMessages(['content' => 'Sólo un administrador puede pasar una página a HTML.']);
        }

        if ($content !== $this->sourceContent($page)) {
            throw ValidationException::withMessages(['content' => 'Sólo un administrador puede cambiar el HTML de una página.']);
        }

        return $content;
    }

    /**
     * HTML de los bloques de HTML libre que ya tiene la página, en cualquiera
     * de sus formatos guardados: los que puso un administrador se conservan.
     *
     * @return list<string>
     */
    private function storedRawHtml(ContentPage $page): array
    {
        if (! $page->exists) {
            return [];
        }

        try {
            $page->unsetRelation('raws')->unsetRelation('currentRawType');
            $json = $this->contentIn($page, ContentPageFormatEnum::EditorJs);
            $blocks = $json === '' ? [] : $this->converter->decodeBlocks($json);
        } catch (Throwable) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (array $block): mixed => $block['type'] === 'raw' ? ($block['data']['html'] ?? null) : null,
            $blocks,
        ), 'is_string'));
    }

    /**
     * Última versión del historial, la que ofrece «Recuperar versión anterior».
     */
    public function latestBackup(ContentPage $page): ?ContentPageVersion
    {
        return $this->history->latest($page);
    }

    /**
     * Formato de una versión del historial.
     */
    public function formatOf(ContentPageVersion $version): ContentPageFormatEnum
    {
        return $version->format;
    }

    /**
     * Versión viva de la página en un formato.
     */
    private function rawFor(ContentPage $page, ContentPageFormatEnum $format): ?ContentPageRaw
    {
        $page->loadMissing('raws.availableType');

        return $page->raws
            ->sortByDesc('updated_at')
            ->first(fn (ContentPageRaw $raw): bool => $raw->availableType?->type === $format->rawType());
    }

    /**
     * @param  array<string, int>  $types
     */
    private function putRaw(ContentPage $page, ContentPageFormatEnum $format, string $content, array $types): void
    {
        $page->raws()->updateOrCreate(
            ['available_page_raw_id' => $types[$format->rawType()]],
            ['content' => $content],
        );
    }

    /**
     * Id de cada formato en `content_available_page_raw`.
     *
     * @return array<string, int>
     */
    private function typeIds(): array
    {
        $ids = ContentAvailablePageRaw::query()
            ->whereIn('type', array_map(fn (ContentPageFormatEnum $format): string => $format->rawType(), ContentPageFormatEnum::cases()))
            ->pluck('id', 'type')
            ->map(fn ($id): int => (int) $id)
            ->all();

        foreach (ContentPageFormatEnum::cases() as $format) {
            if (! isset($ids[$format->rawType()])) {
                throw new RuntimeException("Falta el tipo «{$format->rawType()}» en content_available_page_raw. Ejecuta el seeder ContentAvailablePageRawSeeder.");
            }
        }

        return $ids;
    }
}
