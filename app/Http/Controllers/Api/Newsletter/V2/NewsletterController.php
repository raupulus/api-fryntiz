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
 * Controlador de newsletter para API V2.
 */
class NewsletterController extends BaseApiController
{
    public function __construct(private NewsletterService $service) {}

    /**
     * Suscribe un email a la newsletter.
     */
    public function subscribe(NewsletterSubscribeRequest $request): JsonResponse
    {
        $this->service->subscribe($request->email, $request->name);

        return $this->createdResponse(message: 'Suscripcion creada. Revisa tu email para verificar.');
    }

    /**
     * Verifica un email de newsletter.
     */
    public function verify(string $token): JsonResponse
    {
        $result = $this->service->verify($token);

        if (! $result) {
            return $this->notFoundResponse('Token invalido');
        }

        return $this->successResponse(message: 'Email verificado correctamente');
    }

    /**
     * Cancela la suscripción a la newsletter.
     */
    public function unsubscribe(string $token): JsonResponse
    {
        $result = $this->service->unsubscribe($token);

        if (! $result) {
            return $this->notFoundResponse('Token invalido');
        }

        return $this->successResponse(message: 'Suscripcion cancelada correctamente');
    }

    /**
     * Reenvía el email de verificación de una suscripción.
     */
    public function resendVerification(NewsletterResendRequest $request): JsonResponse
    {
        $newsletter = $this->service->resendVerification(
            $request->validated('email'),
            (int) $request->validated('platform_id')
        );

        if (! $newsletter) {
            return $this->notFoundResponse('Suscripcion no encontrada');
        }

        return $this->successResponse(message: 'Email de verificacion reenviado correctamente');
    }

    /**
     * Estadísticas de la newsletter (opcional ?platform_id=).
     */
    public function stats(Request $request): JsonResponse
    {
        $platformId = $request->integer('platform_id') ?: null;

        return $this->successResponse($this->service->stats($platformId));
    }
}
