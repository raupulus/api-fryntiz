<?php

declare(strict_types=1);

namespace App\Services\Newsletter;

use App\Mail\NewsletterVerification;
use App\Models\Newsletter;
use App\Models\Platform;
use Illuminate\Support\Facades\Mail;

/**
 * Servicio de gestión de la Newsletter: suscripciones, verificaciones y cancelaciones.
 */
class NewsletterService
{
    /**
     * Inscribe un nuevo email en la newsletter y emite un correo de verificación.
     *
     * @param  string  $email  Dirección de correo electrónico.
     * @param  string|null  $name  Nombre opcional del suscriptor.
     * @return Newsletter Modelo de la suscripción generada.
     */
    public function subscribe(string $email, ?string $name = null): Newsletter
    {
        // Resolve platform_id
        $platformId = request('platform_id');
        if (! $platformId) {
            $platformId = Platform::where('domain', request()->getHost())->first()?->id
                ?? (Platform::first()?->id ?? 1);
        }

        $result = Newsletter::createOrUpdate([
            'email' => $email,
            'name' => $name,
            'platform_id' => $platformId,
            'is_verified' => false,
            'status' => Newsletter::STATUS_INACTIVE,
            'subscription_source' => Newsletter::SOURCE_API,
            'language' => request()->header('Accept-Language') ? substr(request()->header('Accept-Language'), 0, 2) : 'es',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $newsletter = $result['newsletter'];

        // Always ensure tokens exist
        if (empty($newsletter->verification_token)) {
            $newsletter->regenerateVerificationToken();
        }

        Mail::to($email)->send(new NewsletterVerification($newsletter));

        return $newsletter;
    }

    /**
     * Valida y activa una suscripción usando un token de verificación.
     *
     * @param  string  $token  Token criptográfico único.
     * @return bool True si se verificó con éxito, False en caso contrario.
     */
    public function verify(string $token): bool
    {
        $newsletter = Newsletter::findByVerificationToken($token);
        if (! $newsletter) {
            return false;
        }

        return $newsletter->verifyEmail();
    }

    /**
     * Da de baja y cancela la suscripción en base a su token de cancelación.
     *
     * @param  string  $token  Token criptográfico de desuscripción.
     * @return bool True si se canceló correctamente, False de lo contrario.
     */
    public function unsubscribe(string $token): bool
    {
        $newsletter = Newsletter::findByUnsubscribeToken($token);
        if (! $newsletter) {
            return false;
        }

        return $newsletter->unsubscribe();
    }

    /**
     * Reenvía el email de verificación para una suscripción existente.
     * Devuelve null si no existe la suscripción.
     */
    public function resendVerification(string $email, int $platformId): ?Newsletter
    {
        $newsletter = Newsletter::where('email', $email)
            ->where('platform_id', $platformId)
            ->first();

        if (! $newsletter) {
            return null;
        }

        if ($newsletter->is_verified) {
            return $newsletter;
        }

        $newsletter->regenerateVerificationToken();
        Mail::to($newsletter->email)->send(new NewsletterVerification($newsletter));

        return $newsletter;
    }

    /**
     * Estadísticas de la newsletter, opcionalmente filtradas por plataforma.
     */
    public function stats(?int $platformId = null): array
    {
        return Newsletter::getStats($platformId);
    }
}
