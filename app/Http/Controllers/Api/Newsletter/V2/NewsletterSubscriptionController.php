<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Newsletter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Newsletter\V2\NewsletterResendRequest;
use App\Http\Requests\Api\Newsletter\V2\NewsletterSubscribeRequest;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Suscripciones a la newsletter, como recurso.
 *
 * El cambio que importa no es el nombre de las URLs: es que **verificar y darse
 * de baja eran peticiones GET**, y esos enlaces viajan dentro de un correo.
 * Gmail, Outlook, los antivirus corporativos y los escáneres de enlaces hacen
 * *prefetch* de las URLs de los mensajes para ver si son maliciosas. Cada
 * prefetch verificaba una suscripción que nadie había confirmado, o daba de
 * baja a alguien que no quería irse.
 *
 * Por eso el enlace del correo apunta ahora a una **página web que no muta
 * nada** (`GET /newsletter/{token}`) con dos botones, y la mutación son los
 * métodos de aquí. Es también lo que exige el RFC 8058 para la baja de un clic.
 */
class NewsletterSubscriptionController extends BaseApiController
{
    public function __construct(private readonly NewsletterService $service) {}

    /**
     * Alta en la newsletter.
     */
    public function store(NewsletterSubscribeRequest $request): JsonResponse
    {
        $this->service->subscribe(
            $request->validated('email'),
            $request->validated('name'),
            (int) $request->validated('platform_id'),
            $request->context(),
        );

        return $this->createdResponse(
            message: 'Suscripcion creada. Revisa tu email para verificar.'
        );
    }

    /**
     * Confirma la suscripción.
     *
     * Antes era `GET /newsletter/verify/{token}`.
     */
    public function confirm(string $token): JsonResponse
    {
        if (! $this->service->verify($token)) {
            return $this->notFoundResponse('Token inválido');
        }

        return $this->successResponse(message: 'Email verificado correctamente');
    }

    /**
     * Baja de la newsletter.
     *
     * Antes era `GET /newsletter/unsubscribe/{token}`.
     */
    public function destroy(string $token): JsonResponse
    {
        if (! $this->service->unsubscribe($token)) {
            return $this->notFoundResponse('Token inválido');
        }

        return $this->deletedResponse();
    }

    /**
     * Baja de un clic (RFC 8058).
     *
     * Es la misma operación que `destroy`, pero por POST y devolviendo 200 con
     * cuerpo, que es lo que espera el cliente de correo al procesar la cabecera
     * `List-Unsubscribe-Post: List-Unsubscribe=One-Click`. Un 204 sin cuerpo
     * confunde a algunos clientes.
     */
    public function unsubscribe(string $token): JsonResponse
    {
        if (! $this->service->unsubscribe($token)) {
            return $this->notFoundResponse('Token inválido');
        }

        return $this->successResponse(message: 'Suscripcion cancelada correctamente');
    }

    /**
     * Reenvía el email de verificación.
     *
     * Responde siempre lo mismo exista o no la suscripción: antes devolvía 404
     * si el email no estaba suscrito y 200 si sí, o sea un oráculo público para
     * comprobar si una dirección está en la lista (auditoría A6).
     */
    public function resendVerification(NewsletterResendRequest $request): JsonResponse
    {
        $this->service->resendVerification(
            $request->validated('email'),
            (int) $request->validated('platform_id')
        );

        return $this->successResponse(
            message: 'Si la dirección está suscrita y pendiente de verificar, se ha enviado el correo.'
        );
    }

    /**
     * Estadísticas. La ruta exige `ability:session` y el gate `view-statistics`.
     */
    public function stats(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->service->stats($request->integer('platform_id') ?: null)
        );
    }
}
