<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentPageFormatEnum;
use App\Enums\ContentPageVersionReasonEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageDraft;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

/**
 * Borradores de las páginas en el servidor (D1 y P4 de la auditoría de
 * contenidos del 2026-09-24).
 *
 * - El editor manda lo que hay en pantalla cada 30 s; sólo se escribe si ha
 *   cambiado respecto al último borrador y respecto a lo guardado (por la
 *   huella, que en Editor.js no cuenta su marca de tiempo). Uno por usuario y
 *   página.
 * - Cada borrador es de quien lo escribió: nadie más lo ve ni lo recupera.
 * - Si la página se ha guardado después de empezar el borrador, se avisa.
 * - Recuperarlo nunca borra lo guardado: lo que había pasa antes al historial
 *   (`draft_restore`).
 * - Se borra al guardar la página (`ContentPageFormatService::savePage()`) y
 *   caduca a los `MAX_AGE_DAYS` días.
 */
class ContentPageDraftService
{
    public const MAX_AGE_DAYS = 30;

    public function __construct(
        private readonly ContentPageFormatService $pages,
    ) {}

    /**
     * Huella de todo lo que guarda un borrador.
     */
    public static function hash(ContentPageFormatEnum $format, string $content, ?string $title, ?string $slug, ?int $imageId): string
    {
        return hash('sha256', implode("\n", [
            ContentPageHistoryService::hash($format, $content),
            (string) $title,
            (string) $slug,
            (string) $imageId,
        ]));
    }

    /**
     * Guarda (o actualiza) el borrador de este usuario para esta página, o
     * para una página nueva del contenido si `$page` es null.
     *
     * @param  CarbonInterface|null  $basePageUpdatedAt  `updated_at` de la página cuando se abrió; por defecto, el de ahora.
     * @return ContentPageDraft|null null si lo escrito es igual a lo guardado (y entonces sobra el borrador que hubiera).
     *
     * @throws InvalidArgumentException si la página no es de ese contenido.
     */
    public function save(
        User $user,
        Content $content,
        ?ContentPage $page,
        ContentPageFormatEnum $format,
        string $text,
        ?string $title = null,
        ?string $slug = null,
        ?int $imageId = null,
        ?CarbonInterface $basePageUpdatedAt = null,
    ): ?ContentPageDraft {
        if ($page !== null && (int) $page->content_id !== $content->id) {
            throw new InvalidArgumentException('La página no es de ese contenido.');
        }

        $hash = self::hash($format, $text, $title, $slug, $imageId);

        if ($page !== null && $hash === $this->savedHash($page)) {
            $this->query($user, $content, $page)->delete();

            return null;
        }

        $draft = $this->find($user, $content, $page);

        if ($draft !== null && $draft->content_hash === $hash) {
            return $draft;
        }

        $draft ??= new ContentPageDraft([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'content_page_id' => $page?->id,
        ]);

        $draft->fill([
            'format' => $format,
            'title' => $title,
            'slug' => $slug,
            'image_id' => $imageId,
            'content' => $text,
            'content_hash' => $hash,
            'base_page_updated_at' => $basePageUpdatedAt ?? $page?->updated_at,
        ])->save();

        return $draft;
    }

    /**
     * El borrador de este usuario para esta página (o para una página nueva).
     * Nunca el de otro.
     */
    public function find(User $user, Content $content, ?ContentPage $page): ?ContentPageDraft
    {
        return $this->query($user, $content, $page)->first();
    }

    /**
     * Si la página se ha guardado después de empezar el borrador: al ofrecerlo
     * se avisa de que «La página ha cambiado desde tu borrador».
     */
    public function isOutdated(ContentPageDraft $draft): bool
    {
        if ($draft->content_page_id === null) {
            return false;
        }

        $updatedAt = DB::table('content_pages')->where('id', $draft->content_page_id)->value('updated_at');

        return $updatedAt !== null
            && ($draft->base_page_updated_at === null || Carbon::parse($updatedAt)->gt($draft->base_page_updated_at));
    }

    /**
     * Aplica el borrador a su página (o crea la página, si era nueva) y lo
     * borra. Lo guardado pasa antes al historial, así que se puede volver a
     * ello.
     *
     * @throws AuthorizationException si el borrador no es suyo o ya no puede editar el contenido.
     */
    public function restore(ContentPageDraft $draft, User $user, ?string $lockToken = null): ContentPage
    {
        $content = $this->ensureOwn($draft, $user);

        $page = $draft->page ?? new ContentPage([
            'content_id' => $content->id,
            'order' => (int) $content->pages()->max('order') + 1,
        ]);

        $this->pages->savePage(
            $page,
            array_filter(['title' => $draft->title, 'slug' => $draft->slug, 'image_id' => $draft->image_id], fn ($value): bool => $value !== null),
            $draft->format,
            $draft->content,
            ContentPageVersionReasonEnum::DraftRestore,
            $user,
            lockToken: $lockToken,
        );

        // `savePage()` borra el borrador de quien guarda; por si el contenido
        // venía vacío y no se guardó, se borra aquí también.
        $draft->delete();

        return $page;
    }

    /**
     * «Descartar»: borra el borrador sin tocar la página.
     *
     * @throws AuthorizationException si no es suyo.
     */
    public function discard(ContentPageDraft $draft, User $user): void
    {
        if ($draft->user_id !== $user->id) {
            throw new AuthorizationException('Este borrador no es tuyo.');
        }

        $draft->delete();
    }

    /**
     * Borra los borradores de más de `MAX_AGE_DAYS` días sin tocar.
     *
     * @return array{deleted: int, content_ids: list<int>}
     */
    public function prune(): array
    {
        $old = ContentPageDraft::query()->where('updated_at', '<', now()->subDays(self::MAX_AGE_DAYS));
        $contentIds = (clone $old)->distinct()->pluck('content_id')->map(fn ($id): int => (int) $id)->all();

        return ['deleted' => (int) $old->delete(), 'content_ids' => $contentIds];
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureOwn(ContentPageDraft $draft, User $user): Content
    {
        if ($draft->user_id !== $user->id) {
            throw new AuthorizationException('Este borrador no es tuyo.');
        }

        $content = $draft->contentModel;

        if ($content === null || Gate::forUser($user)->denies('update', $content)) {
            throw new AuthorizationException('Ya no puedes editar este contenido.');
        }

        return $content;
    }

    /**
     * La huella de lo que la página tiene guardado.
     */
    private function savedHash(ContentPage $page): string
    {
        $page->unsetRelation('raws')->unsetRelation('currentRawType');

        return self::hash(
            $this->pages->sourceFormat($page),
            $this->pages->sourceContent($page),
            $page->title,
            $page->slug,
            $page->image_id !== null ? (int) $page->image_id : null,
        );
    }

    /**
     * @return Builder<ContentPageDraft>
     */
    private function query(User $user, Content $content, ?ContentPage $page): Builder
    {
        $query = ContentPageDraft::query()->where('user_id', $user->id)->where('content_id', $content->id);

        return $page === null ? $query->whereNull('content_page_id') : $query->where('content_page_id', $page->id);
    }
}
