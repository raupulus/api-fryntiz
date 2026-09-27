<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\Content\ContentContributor;
use App\Models\PlatformUser;
use App\Models\User;

/**
 * Colaboradores de un contenido (B3 y DUDA-1 del plan de contenidos del
 * 2026-09-24).
 *
 * Quitar a un colaborador deja su fila en `content_contributors` **borrada**,
 * no la elimina: esa fila es lo que distingue una baja manual, y el
 * colaborador automático la respeta (no vuelve a añadirle). Volver a añadirle a
 * mano recupera la fila.
 */
class ContentContributorService
{
    public function add(Content $content, User $user): void
    {
        if ((int) $content->author_id === (int) $user->id) {
            return;
        }

        $row = ContentContributor::withTrashed()
            ->where('content_id', $content->id)
            ->where('user_id', $user->id)
            ->first();

        if ($row === null) {
            ContentContributor::query()->create(['content_id' => $content->id, 'user_id' => $user->id]);
        } elseif ($row->trashed()) {
            $row->restore();
        }
    }

    public function remove(Content $content, User $user): void
    {
        ContentContributor::query()
            ->where('content_id', $content->id)
            ->where('user_id', $user->id)
            ->delete();

        // Borrado en bloque, sin eventos: la API deja de enseñarlo aquí.
        Content::markChanged($content->id);
    }

    /**
     * Al crear un contenido: entran los Editores con el colaborador automático
     * en su plataforma, salvo el autor.
     */
    public function applyToNewContent(Content $content): void
    {
        if ($content->platform_id === null) {
            return;
        }

        $editors = User::query()
            ->whereIn('id', PlatformUser::query()
                ->where('platform_id', $content->platform_id)
                ->where('auto_contributor', true)
                ->select('user_id'))
            ->where('role_id', UserRoleEnum::Editor->value)
            ->get();

        foreach ($editors as $editor) {
            $this->add($content, $editor);
        }
    }

    /**
     * Al activar el colaborador automático: el Editor entra en los contenidos
     * que ya existen en la plataforma, salvo en los suyos y en los que se le
     * quitó a mano (tienen su fila borrada).
     *
     * @return int En cuántos contenidos ha entrado.
     */
    public function applyToExistingContents(PlatformUser $assignment): int
    {
        $user = $assignment->user;

        if ($user === null || ! $user->isEditor()) {
            return 0;
        }

        $contents = Content::query()
            ->where('platform_id', $assignment->platform_id)
            ->where(fn ($query) => $query->whereNull('author_id')->orWhere('author_id', '!=', $user->id))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('content_contributors')
                ->whereColumn('content_contributors.content_id', 'contents.id')
                ->where('content_contributors.user_id', $user->id))
            ->pluck('id');

        foreach ($contents as $contentId) {
            ContentContributor::query()->create(['content_id' => $contentId, 'user_id' => $user->id]);
        }

        return $contents->count();
    }
}
