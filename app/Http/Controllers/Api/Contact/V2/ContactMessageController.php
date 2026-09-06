<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Contact\V2;

use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Contact\V2\ContactSendRequest;
use App\Services\Contact\ContactService;
use App\Services\RecaptchaService;
use App\Support\Http\ClientIp;
use Illuminate\Http\JsonResponse;

/**
 * Mensajes de contacto.
 *
 * El recurso es el mensaje, no la acción de enviarlo: `POST /contact-messages`.
 *
 * La respuesta es **siempre la misma** pase el filtro o no. Decirle al que
 * escribe «tu mensaje parece spam» le enseña exactamente qué cambiar para que
 * la próxima vez pase, y a quien escribe de buena fe le da un susto para nada.
 * Lo dudoso queda en el panel.
 */
class ContactMessageController extends BaseApiController
{
    public function __construct(
        private readonly ContactService $contactService,
        private readonly RecaptchaService $recaptchaService,
    ) {}

    public function store(ContactSendRequest $request): JsonResponse
    {
        // La IP que interesa aquí es la del visitante, no la del proxy: es la
        // que Google usa para valorar el riesgo, y la que se guarda con el
        // mensaje. Cae a la IP de conexión si no hay proxy delante.
        $ipOrigen = ClientIp::public($request) ?? $request->ip();

        $captcha = $this->recaptchaService->verify(
            $request->validated('g-recaptcha-response'),
            $ipOrigen,
        );

        // Un captcha inválido con claves configuradas sí se rechaza de plano:
        // ahí no hay duda posible y no interesa guardar la basura.
        if ($captcha->configured && ! $captcha->valid) {
            return $this->errorResponse('Verificacion de seguridad fallida', 422);
        }

        $this->contactService->register(
            $request->validated(),
            [
                'ip' => $ipOrigen,
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'accept_language' => $request->header('accept-language'),
                'host' => $request->getHost(),
            ],
            $captcha,
        );

        return $this->createdResponse(message: 'Mensaje recibido correctamente');
    }
}
