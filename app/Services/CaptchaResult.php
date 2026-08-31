<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Resultado de verificar un captcha.
 *
 * `configurado` distingue «no hay claves puestas» de «Google dice que es
 * humano». Sin esa distinción, un entorno sin configurar parecería que valida
 * todo perfectamente.
 */
final class CaptchaResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?float $score,
        public readonly bool $configured,
    ) {}

    /**
     * ¿La puntuación está por debajo del corte configurado?
     *
     * Sin puntuación (v2 clásico, o Google caído) no se puede afirmar que sea
     * sospechoso: devuelve false.
     */
    public function isSuspicious(): bool
    {
        if ($this->score === null) {
            return false;
        }

        return $this->score < (float) config('contact.captcha.threshold', 0.5);
    }
}
