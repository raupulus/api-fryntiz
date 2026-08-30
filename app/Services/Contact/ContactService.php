<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\Jobs\SendContactEmailJob;
use App\Models\Email;
use App\Models\Language;
use App\Models\Platform;
use App\Services\CaptchaResult;
use Illuminate\Support\Str;

/**
 * Mensajes del formulario de contacto.
 *
 * El diseño (C4): las webs hacen POST aquí, este backend decide si el mensaje
 * parece legítimo, y si lo parece lo reenvía al buzón del dueño de la
 * plataforma. Lo dudoso **no se tira**: se guarda con prioridad baja y queda en
 * el panel, que es la diferencia entre filtrar spam y perder mensajes.
 *
 * Lo que había antes: se validaba el captcha y se mandaba el correo. El mensaje
 * no se guardaba en ningún sitio, así que el listado de correos del panel
 * estaba vacío para siempre y no había forma de revisar nada.
 */
class ContactService
{
    /**
     * Registra un mensaje de contacto y lo encola si supera el umbral.
     *
     * @param  array{name:string,email:string,subject:string,message:string,privacity?:bool,contactme?:bool,attributes?:array}  $data
     * @param  array{ip?:string|null,user_agent?:string|null,referer?:string|null,accept_language?:string|null,host?:string|null}  $context
     */
    public function register(array $data, array $context, CaptchaResult $captcha): Email
    {
        $platform = $this->platformOf($context['host'] ?? null, $context['referer'] ?? null);

        $subject = $this->sanitise($data['subject']);
        $message = $this->sanitise($data['message']);

        $priority = $this->calculatePriority($data, $context, $captcha, $message);
        $duplicate = $this->isDuplicate($data['email'], $subject, $message, $context['ip'] ?? null);

        if ($duplicate) {
            // No se descarta: se guarda con prioridad 0 para que quede el
            // rastro de cuántas veces se ha intentado.
            $priority = 0;
        }

        $umbral = (int) config('contact.priority.send_threshold', 3);
        $seEnvia = $priority >= $umbral;

        $email = new Email;
        $email->forceFill([
            // Destinatario: el dueño de la plataforma desde la que se escribe.
            'user_id' => $platform?->user_id,
            'platform_id' => $platform?->id,
            'language_id' => $this->localeOf($context['accept_language'] ?? null),
            'email' => $data['email'],
            'subject' => $subject,
            'message' => $message,
            'privacity' => (bool) ($data['privacity'] ?? false),
            'contactme' => (bool) ($data['contactme'] ?? false),
            'server_ip' => request()->server('SERVER_ADDR'),
            'client_ip' => $context['ip'] ?? null,
            'client_user_agent' => $this->truncate($context['user_agent'] ?? null, 512),
            'client_referer' => $this->truncate($context['referer'] ?? null, 512),
            'client_accept_language' => $this->acceptedLocales($context['accept_language'] ?? null),
            'app_name' => $platform?->title,
            'app_domain' => $context['host'] ?? null,
            'captcha_score' => $captcha->score,
            'priority' => $priority,
            'send' => $seEnvia,
            'attempts' => 0,
            'attributes' => $this->buildAttributes($data, $captcha, $duplicate),
        ])->save();

        if ($seEnvia) {
            SendContactEmailJob::dispatch($email->id);
        }

        return $email;
    }

    /**
     * Prioridad de 0 a 10. Cuanto más alta, más confianza.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $context
     */
    private function calculatePriority(array $data, array $context, CaptchaResult $captcha, string $message): int
    {
        $priority = (int) config('contact.priority.base', 5);

        // Bloqueo duro: ni se plantea enviarlo.
        if ($this->isBlocked($data['email'], $context['ip'] ?? null)) {
            return 0;
        }

        // Captcha.
        if ($captcha->configured) {
            if (! $captcha->valid) {
                return 0;
            }

            if ($captcha->score !== null) {
                // 0.0 resta 3, 1.0 suma 2.
                $priority += (int) round($captcha->score * 5) - 3;
            }
        }

        // Confianza declarada.
        if ($this->isListed($context['host'] ?? null, (array) config('contact.trust.domains'))) {
            $priority += 2;
        }

        if (in_array($context['ip'] ?? '', (array) config('contact.trust.ips'), true)) {
            $priority += 2;
        }

        // Señales de spam en el texto.
        $enlaces = preg_match_all('#https?://#i', $message);
        $maxEnlaces = (int) config('contact.spam_signals.max_links', 2);

        if ($enlaces > $maxEnlaces) {
            $priority -= ($enlaces - $maxEnlaces);
        }

        $textoPlano = mb_strtolower($message.' '.$data['subject']);

        foreach ((array) config('contact.spam_signals.words', []) as $palabra) {
            if (str_contains($textoPlano, mb_strtolower((string) $palabra))) {
                $priority--;
            }
        }

        // Un mensaje sin referer y sin user agent no viene de un navegador.
        if (empty($context['referer']) && empty($context['user_agent'])) {
            $priority -= 2;
        }

        return max(0, min(10, $priority));
    }

    /**
     * Anti-duplicado en dos ventanas.
     *
     * La corta ataca el doble clic; la larga, el mismo texto repetido desde la
     * misma IP durante un día.
     */
    private function isDuplicate(string $email, string $subject, string $message, ?string $ip): bool
    {
        $minutes = (int) config('contact.deduplication.minutes_per_email', 2);

        $reciente = Email::query()
            ->where('email', $email)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->exists();

        if ($reciente) {
            return true;
        }

        $hours = (int) config('contact.deduplication.hours_per_content', 24);

        return Email::query()
            ->where('subject', $subject)
            ->where('message', $message)
            ->when($ip !== null, fn ($q) => $q->where('client_ip', $ip))
            ->where('created_at', '>=', now()->subHours($hours))
            ->exists();
    }

    /**
     * Limpia el texto antes de guardarlo.
     *
     * Estos mensajes acaban en dos sitios peligrosos: un correo HTML y el panel
     * de administración. Se quitan las etiquetas, se normalizan los saltos de
     * línea y se recortan los caracteres de control, que es por donde se cuelan
     * las cabeceras falsas en un correo.
     */
    private function sanitise(string $text): string
    {
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Caracteres de control salvo el salto de línea y el tabulador.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;

        return trim($text);
    }

    private function truncate(?string $value, int $maximum): ?string
    {
        if ($value === null) {
            return null;
        }

        return Str::limit($this->sanitise($value), $maximum, '');
    }

    /**
     * Plataforma a la que va dirigido el mensaje.
     *
     * Se resuelve por el host de la petición y, si no cuadra, por el dominio
     * del referer: el formulario vive en la web del cliente, no aquí.
     */
    private function platformOf(?string $host, ?string $referer): ?Platform
    {
        $candidates = array_filter([
            $host,
            $referer === null ? null : parse_url($referer, PHP_URL_HOST),
        ]);

        foreach ($candidates as $domain) {
            $platform = Platform::query()
                ->where('domain', $domain)
                ->orWhere('domain', preg_replace('/^www\./', '', (string) $domain))
                ->first();

            if ($platform) {
                return $platform;
            }
        }

        return null;
    }

    /**
     * Idioma del remitente a partir de `Accept-Language`, si lo conocemos.
     */
    private function localeOf(?string $acceptLanguage): ?int
    {
        if ($acceptLanguage === null || $acceptLanguage === '') {
            return null;
        }

        $iso2 = mb_strtolower(mb_substr(trim(explode(',', $acceptLanguage)[0]), 0, 2));

        return Language::query()->where('iso2', $iso2)->value('id');
    }

    /**
     * Lista de idiomas aceptados, para poder responder en el suyo.
     *
     * @return array<int, string>|null
     */
    private function acceptedLocales(?string $acceptLanguage): ?array
    {
        if ($acceptLanguage === null || $acceptLanguage === '') {
            return null;
        }

        $locales = [];

        foreach (explode(',', $acceptLanguage) as $chunk) {
            $code = trim(explode(';', $chunk)[0]);

            if ($code !== '') {
                $locales[] = mb_substr($code, 0, 16);
            }
        }

        return array_slice(array_values(array_unique($locales)), 0, 10) ?: null;
    }

    /**
     * Datos extra que ayudan a decidir a mano si un mensaje dudoso es legítimo.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildAttributes(array $data, CaptchaResult $captcha, bool $duplicate): array
    {
        return array_filter([
            'name' => isset($data['name']) ? $this->sanitise((string) $data['name']) : null,
            'captcha_configurado' => $captcha->configured,
            'captcha_valido' => $captcha->valid,
            'duplicado' => $duplicate,
            'extra' => $data['attributes'] ?? null,
        ], fn ($v) => $v !== null);
    }

    private function isBlocked(string $email, ?string $ip): bool
    {
        $domain = mb_strtolower((string) Str::after($email, '@'));

        if ($domain !== '' && in_array($domain, array_map('mb_strtolower', (array) config('contact.blocking.email_domains', [])), true)) {
            return true;
        }

        return $ip !== null && in_array($ip, (array) config('contact.blocking.ips', []), true);
    }

    /**
     * @param  array<int, string>  $items
     */
    private function isListed(?string $value, array $items): bool
    {
        if ($value === null || $items === []) {
            return false;
        }

        $value = mb_strtolower(preg_replace('/^www\./', '', $value) ?? $value);

        foreach ($items as $permitido) {
            if ($value === mb_strtolower(preg_replace('/^www\./', '', (string) $permitido) ?? (string) $permitido)) {
                return true;
            }
        }

        return false;
    }
}
