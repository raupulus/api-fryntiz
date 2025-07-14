<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\NewsletterSubscribeRequest;
use App\Models\Newsletter;
use App\Mail\NewsletterVerification;
use App\Mail\NewsletterUnsubscribe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    /**
     * Suscribir a un usuario a la newsletter
     *
     * @param NewsletterSubscribeRequest $request
     * @return JsonResponse
     */
    public function subscribe(NewsletterSubscribeRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Crear o actualizar suscriptor
            $result = Newsletter::createOrUpdate($data);
            $newsletter = $result['newsletter'];
            $isNew = $result['isNew'];

            // Enviar email de verificación solo si es un nuevo suscriptor
            if ($isNew) {
                $this->sendVerificationEmail($newsletter);
            }

            return \JsonHelper::success([
                'message' => 'Te has suscrito correctamente. Revisa tu email para confirmar la suscripción.',
                'data' => [
                    'email' => $newsletter->email,
                    'status' => $newsletter->status,
                    'is_verified' => $newsletter->is_verified,
                ]
            ]);

        } catch (\Exception $e) {
            return \JsonHelper::failed(
                'Error al procesar la suscripción',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Verificar email de suscripción
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function verify(Request $request, string $token): JsonResponse
    {
        try {
            $newsletter = Newsletter::findByVerificationToken($token);

            if (!$newsletter) {
                return \JsonHelper::notFound('Token de verificación no válido');
            }

            if ($newsletter->is_verified) {
                return \JsonHelper::success([
                    'message' => 'Tu email ya estaba verificado',
                    'data' => ['email' => $newsletter->email]
                ]);
            }

            $newsletter->verifyEmail();

            return \JsonHelper::success([
                'message' => 'Email verificado correctamente. Ya estás suscrito a la newsletter.',
                'data' => [
                    'email' => $newsletter->email,
                    'verified_at' => $newsletter->verified_at,
                ]
            ]);

        } catch (\Exception $e) {
            return \JsonHelper::failed(
                'Error al verificar el email',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Desuscribir usuario
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function unsubscribe(Request $request, string $token): JsonResponse
    {
        try {
            $newsletter = Newsletter::findByUnsubscribeToken($token);

            if (!$newsletter) {
                return \JsonHelper::notFound('Token de desuscripción no válido');
            }

            if ($newsletter->isUnsubscribed()) {
                return \JsonHelper::success([
                    'message' => 'Ya estabas desuscrito de la newsletter',
                    'data' => ['email' => $newsletter->email]
                ]);
            }

            $newsletter->unsubscribe();

            return \JsonHelper::success([
                'message' => 'Te has desuscrito correctamente de la newsletter.',
                'data' => [
                    'email' => $newsletter->email,
                    'unsubscribed_at' => $newsletter->unsubscribed_at,
                ]
            ]);

        } catch (\Exception $e) {
            return \JsonHelper::failed(
                'Error al desuscribir',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Reenviar email de verificación
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'platform_id' => 'required|exists:platforms,id',
        ]);

        try {
            $newsletter = Newsletter::where('email', $request->email)
                ->where('platform_id', $request->platform_id)
                ->first();

            if (!$newsletter) {
                return \JsonHelper::notFound('Suscripción no encontrada');
            }

            if ($newsletter->is_verified) {
                return \JsonHelper::success([
                    'message' => 'Tu email ya está verificado',
                    'data' => ['email' => $newsletter->email]
                ]);
            }

            // Regenerar token y enviar email
            $newsletter->regenerateVerificationToken();
            $this->sendVerificationEmail($newsletter);

            return \JsonHelper::success([
                'message' => 'Email de verificación reenviado correctamente',
                'data' => ['email' => $newsletter->email]
            ]);

        } catch (\Exception $e) {
            return \JsonHelper::failed(
                'Error al reenviar verificación',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Obtener estadísticas de la newsletter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $platformId = $request->get('platform_id');
            $stats = Newsletter::getStats($platformId);

            return \JsonHelper::success([
                'message' => 'Estadísticas obtenidas correctamente',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return \JsonHelper::failed(
                'Error al obtener estadísticas',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Enviar email de verificación
     *
     * @param Newsletter $newsletter
     * @return void
     */
    private function sendVerificationEmail(Newsletter $newsletter): void
    {
        Mail::to($newsletter->email)->send(new NewsletterVerification($newsletter));
    }
}
