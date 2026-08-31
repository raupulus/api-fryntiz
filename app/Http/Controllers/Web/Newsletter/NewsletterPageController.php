<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * La página que abre el destinatario desde el correo.
 *
 * Existe para que el enlace del correo **no mute nada**. Los clientes de correo
 * y los antivirus hacen prefetch de las URLs de los mensajes; mientras
 * verificar y darse de baja eran peticiones GET, ese prefetch confirmaba
 * suscripciones que nadie había confirmado y daba de baja a gente que no se
 * quería ir.
 *
 * Aquí el GET sólo pinta la página. Las dos acciones son POST.
 */
class NewsletterPageController extends Controller
{
    public function __construct(private readonly NewsletterService $service) {}

    /**
     * Página de gestión. No cambia nada.
     */
    public function show(string $token): View
    {
        $subscription = $this->byToken($token);

        return view('newsletter.manage', [
            'token' => $token,
            'subscription' => $subscription,
            'isVerificationLink' => $subscription !== null
                && $subscription->verification_token === $token,
        ]);
    }

    /**
     * Confirma la suscripción y vuelve a la página con el resultado.
     */
    public function confirm(string $token): RedirectResponse
    {
        $ok = $this->service->verify($token);

        return redirect()
            ->route('newsletter.manage', ['token' => $token])
            ->with('newsletter_status', $ok ? 'verified' : 'invalid_token');
    }

    /**
     * Da de baja y vuelve a la página con el resultado.
     */
    public function unsubscribe(string $token): RedirectResponse
    {
        $ok = $this->service->unsubscribe($token);

        return redirect()
            ->route('newsletter.manage', ['token' => $token])
            ->with('newsletter_status', $ok ? 'unsubscribed' : 'invalid_token');
    }

    /**
     * Busca la suscripción por cualquiera de sus dos tokens.
     *
     * El de verificación y el de baja son distintos, y el enlace del correo
     * lleva uno u otro según de qué correo venga.
     */
    private function byToken(string $token): ?Newsletter
    {
        return Newsletter::query()
            ->where('verification_token', $token)
            ->orWhere('unsubscribe_token', $token)
            ->first();
    }
}
