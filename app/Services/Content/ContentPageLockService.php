<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Exceptions\ContentPageLockedException;
use App\Models\Content\ContentPage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Bloqueo de cada página a un usuario mientras la edita (P4 de la auditoría de
 * contenidos del 2026-09-24).
 *
 * - Quien abre la página para editarla la bloquea con un `lock_token` propio
 *   de esa pestaña. Los demás, y sus otras pestañas, la ven en lectura.
 * - Se renueva con cada autoguardado y caduca a los `TTL_SECONDS` sin renovar:
 *   si se cierra la pestaña o se cae la conexión, se libera sola.
 * - Un administrador puede forzar el desbloqueo; el desplazado recibe «bloqueo
 *   perdido» en su siguiente renovación, y lo que tuviera sigue en su borrador.
 *
 * Todo se escribe con el constructor de consultas, no con Eloquent: el bloqueo
 * no puede tocar `updated_at`, que es lo que dice si la página se ha guardado
 * mientras se editaba (D4). Coger el bloqueo lee la fila con `FOR UPDATE`
 * dentro de una transacción, así que dos pestañas a la vez no pueden
 * quedárselo las dos.
 */
class ContentPageLockService
{
    public const TTL_SECONDS = 120;

    /**
     * Intenta coger el bloqueo. Si lo tiene otro (u otra pestaña del mismo
     * usuario), no cambia nada y dice quién.
     */
    public function acquire(ContentPage $page, User $user, string $token): ContentPageLockState
    {
        return DB::transaction(function () use ($page, $user, $token): ContentPageLockState {
            $state = $this->stateOf($this->row($page, forUpdate: true), $user, $token);

            if (! $state->canEdit()) {
                return $state;
            }

            // Si ya era suyo en esta pestaña, conserva desde cuándo.
            $since = $state->status === ContentPageLockState::MINE && $state->since !== null ? $state->since : now();

            DB::table('content_pages')->where('id', $page->id)->update([
                'locked_by_user_id' => $user->id,
                'lock_token' => $token,
                'locked_since' => $since,
                'locked_at' => now(),
            ]);

            return ContentPageLockState::mine($since);
        });
    }

    /**
     * Renueva el bloqueo de esta pestaña.
     *
     * @return bool false = bloqueo perdido: caducó y lo cogió otro, o un
     *              administrador lo forzó. Lo escrito sigue en el borrador.
     */
    public function renew(ContentPage $page, User $user, string $token): bool
    {
        return DB::table('content_pages')
            ->where('id', $page->id)
            ->where('locked_by_user_id', $user->id)
            ->where('lock_token', $token)
            ->update(['locked_at' => now()]) === 1;
    }

    /**
     * Suelta el bloqueo de esta pestaña (al salir del editor). Si no era suyo,
     * no hace nada.
     */
    public function release(ContentPage $page, User $user, string $token): void
    {
        DB::table('content_pages')
            ->where('id', $page->id)
            ->where('locked_by_user_id', $user->id)
            ->where('lock_token', $token)
            ->update($this->unlocked());
    }

    /**
     * «Forzar desbloqueo»: sólo administradores.
     *
     * @throws AuthorizationException
     */
    public function forceUnlock(ContentPage $page, User $by): void
    {
        if (! $by->isAdmin()) {
            throw new AuthorizationException('Sólo un administrador puede forzar el desbloqueo de una página.');
        }

        DB::table('content_pages')->where('id', $page->id)->update($this->unlocked());
    }

    /**
     * Cómo está el bloqueo para este usuario y esta pestaña.
     */
    public function state(ContentPage $page, ?User $user = null, ?string $token = null): ContentPageLockState
    {
        return $this->stateOf($this->row($page), $user, $token);
    }

    /**
     * Guardar sólo si nadie más tiene el bloqueo: otro usuario, u otra pestaña
     * del mismo. Sin token (el modal de la ficha, que no bloquea) también se
     * rechaza si alguien lo tiene.
     *
     * @throws ContentPageLockedException
     */
    public function assertCanSave(ContentPage $page, ?User $user, ?string $token): void
    {
        $state = $this->state($page, $user, $token);

        if (! $state->canEdit()) {
            throw new ContentPageLockedException((string) $state->message());
        }
    }

    private function stateOf(?stdClass $row, ?User $user, ?string $token): ContentPageLockState
    {
        if ($row === null || $row->locked_by_user_id === null || $row->locked_at === null
            || Carbon::parse($row->locked_at)->lt($this->expiredBefore())) {
            return ContentPageLockState::free();
        }

        $holderId = (int) $row->locked_by_user_id;
        $since = $row->locked_since !== null ? Carbon::parse($row->locked_since) : null;

        if ($user !== null && $holderId === $user->id) {
            return $token !== null && hash_equals((string) $row->lock_token, $token)
                ? ContentPageLockState::mine($since)
                : ContentPageLockState::otherTab($holderId, $since);
        }

        $name = User::query()->whereKey($holderId)->value('name');

        return ContentPageLockState::otherUser($holderId, is_string($name) && $name !== '' ? $name : 'Otra persona', $since);
    }

    private function row(ContentPage $page, bool $forUpdate = false): ?stdClass
    {
        $query = DB::table('content_pages')->where('id', $page->id);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->first(['locked_by_user_id', 'locked_since', 'locked_at', 'lock_token']);
    }

    private function expiredBefore(): Carbon
    {
        return now()->subSeconds(self::TTL_SECONDS);
    }

    /**
     * @return array<string, null>
     */
    private function unlocked(): array
    {
        return ['locked_by_user_id' => null, 'locked_since' => null, 'locked_at' => null, 'lock_token' => null];
    }
}
