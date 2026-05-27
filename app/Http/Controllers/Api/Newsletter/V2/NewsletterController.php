<?php

namespace App\Http\Controllers\Api\Newsletter\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Newsletter\V2\NewsletterSubscribeRequest;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Http\JsonResponse;

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
}
