<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\User;
use App\Services\Content\ContentPageLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Lo que la pantalla de páginas pide fuera de Livewire (F8 del plan de
 * contenidos del 2026-09-24).
 */
class ContentPageEditorController extends Controller
{
    /**
     * El token CSRF vigente, justo antes de cada subida (D3): el que se pintó
     * al cargar la página deja de valer si la sesión se ha renovado mientras
     * se escribía.
     *
     * `$content` no se usa aquí, pero tiene que estar: Laravel sólo convierte
     * el `{content}` de la ruta en el modelo si el método lo pide, y sin eso
     * `can:update,content` recibía el texto «1» y denegaba a todos menos al
     * SuperAdmin (que se salta las políticas).
     */
    public function csrfToken(Content $content): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    /**
     * Suelta el bloqueo al cerrar la pestaña (`navigator.sendBeacon`, P4). Si
     * no era de esa pestaña, no hace nada.
     */
    public function releaseLock(Request $request, Content $content, ContentPage $page, ContentPageLockService $locks): Response
    {
        abort_unless((int) $page->content_id === $content->id, 404);

        $user = $request->user();

        if ($user instanceof User) {
            $locks->release($page, $user, (string) $request->input('token'));
        }

        return response()->noContent();
    }
}
