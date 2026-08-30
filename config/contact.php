<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Formulario de contacto
|--------------------------------------------------------------------------
|
| El diseño (C4): los formularios de las webs hacen POST aquí, este backend
| decide si el mensaje parece legítimo y, si lo parece, lo reenvía al buzón del
| dueño de la plataforma. Si es dudoso NO se tira: se queda en el panel para
| poder revisarlo, que es la diferencia entre filtrar spam y perder mensajes.
|
| v2 conservaba el reCAPTCHA y había perdido todo lo demás — el mensaje ni
| siquiera se guardaba, así que el listado de correos del panel estaba vacío
| para siempre.
|
*/

return [

    /*
     * Prioridad 0–10. Por debajo del umbral el mensaje se guarda pero no se
     * envía: queda en el panel marcado como dudoso.
     */
    'priority' => [
        'base' => 5,
        'send_threshold' => (int) env('CONTACT_PRIORITY_THRESHOLD', 3),
    ],

    /*
     * Anti-duplicado. Dos ventanas distintas porque atacan a dos cosas
     * distintas: la primera evita el doble clic y el reenvío nervioso; la
     * segunda evita que se repita el mismo texto desde la misma IP durante un
     * día entero.
     */
    'deduplication' => [
        'minutes_per_email' => (int) env('CONTACT_DEDUPE_EMAIL_MINUTES', 2),
        'hours_per_content' => (int) env('CONTACT_DEDUPE_CONTENT_HOURS', 24),
    ],

    /*
     * Puntuación de reCAPTCHA v3 (0.0 a 1.0) por debajo de la cual el mensaje
     * se considera dudoso. Google recomienda 0.5 como punto de corte.
     */
    'captcha' => [
        'threshold' => (float) env('CONTACT_CAPTCHA_THRESHOLD', 0.5),
    ],

    /*
     * Listas de confianza. Vacías = no se aplica el bonus, no que se bloquee
     * todo. Bloquear por lista blanca dejaría fuera a cualquier visitante
     * legítimo.
     */
    'trust' => [
        'domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONTACT_TRUSTED_DOMAINS', ''))))),
        'ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONTACT_TRUSTED_IPS', ''))))),
        'apps' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONTACT_TRUSTED_APPS', ''))))),
    ],

    /*
     * Listas de bloqueo. Lo que caiga aquí se guarda con prioridad 0 y no se
     * envía nunca.
     */
    'blocking' => [
        'email_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONTACT_BLOCKED_EMAIL_DOMAINS', ''))))),
        'ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('CONTACT_BLOCKED_IPS', ''))))),
    ],

    /*
     * Señales de spam en el texto. Cada coincidencia resta un punto.
     */
    'spam_signals' => [
        'max_links' => (int) env('CONTACT_MAX_LINKS', 2),
        'words' => [
            'seo services', 'buy now', 'crypto', 'casino', 'viagra',
            'backlinks', 'guest post', 'bitcoin', 'forex', 'loan offer',
        ],
    ],

];
