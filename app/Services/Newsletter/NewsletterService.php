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
     * El servicio ya no lee de `request()`: recibe todo lo que necesita. Antes
     * sacaba `platform_id` de la petición **sin validarlo** (ni `integer`, ni
     * `exists`), de modo que un valor inexistente reventaba contra la clave
     * foránea y devolvía 500 en vez de 422; y encima hacía `?? 1` a pelo. Con
     * eso el servicio tampoco se podía llamar desde un comando o un test sin
     * montar una petición falsa (auditoría A7).
     *
     * @param  string  $email  Dirección de correo electrónico.
     * @param  string|null  $name  Nombre opcional del suscriptor.
     * @param  int  $platformId  Plataforma a la que se suscribe (ya validada).
     * @param  array{language?: string|null, ip_address?: string|null, user_agent?: string|null}  $context
     * @return Newsletter Modelo de la suscripción generada.
     */
    public function subscribe(string $email, ?string $name, int $platformId, array $context = []): Newsletter
    {
        $result = Newsletter::createOrUpdate([
            'email' => $email,
            'name' => $name,
            'platform_id' => $platformId,
            'is_verified' => false,
            'status' => Newsletter::STATUS_INACTIVE,
            'subscription_source' => Newsletter::SOURCE_API,
            'language' => $context['language'] ?? 'es',
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
        ]);

        $newsletter = $result['newsletter'];

        // Always ensure tokens exist
        if (empty($newsletter->verification_token)) {
            $newsletter->regenerateVerificationToken();
        }

        // La cabecera RFC 8058 se añade en el propio Mailable, para que la baja
        // de un clic del cliente de correo haga POST y no GET.
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

    /**
     * Plataforma que corresponde a un dominio. Devuelve null si no hay ninguna:
     * el que llama decide qué hacer, en vez de caer a `1` a ciegas.
     */
    public function platformByDomain(string $domain): ?Platform
    {
        return Platform::query()
            ->where('domain', $domain)
            ->first();
    }
}
