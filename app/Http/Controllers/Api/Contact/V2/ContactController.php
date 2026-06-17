<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Contact\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Contact\V2\ContactSendRequest;
use App\Services\Contact\ContactService;
use App\Services\RecaptchaService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de formulario de contacto para API V2.
 */
class ContactController extends BaseApiController
{
    public function __construct(
        private ContactService $contactService,
        private RecaptchaService $recaptchaService,
    ) {}

    /**
     * Envía un formulario de contacto.
     */
    public function send(ContactSendRequest $request): JsonResponse
    {
        if (! $this->recaptchaService->verify($request->validated()['g-recaptcha-response'])) {
            return $this->errorResponse('Verificacion de seguridad fallida', 422);
        }

        $this->contactService->sendContactForm($request->validated());

        return $this->successResponse(message: 'Mensaje enviado correctamente');
    }
}
