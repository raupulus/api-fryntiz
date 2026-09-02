<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un dato simple y de longitud acotada.
 *
 * `extra` es un cajón de métricas que se guarda en JSON, no un sitio donde
 * meter un árbol entero: sin esto, una fila de `hardware_devices` puede crecer
 * sin freno con lo que mande un cacharro (o quien le robe el token).
 *
 * Estaba escrito como método privado dentro de `StoreDeviceStatusRequest`, así
 * que sólo protegía a esa ruta. Se saca aquí para que lo use también
 * {@see DeviceStatusPayload}, que cubre las otras cinco (AR-V01).
 */
class SimpleBoundedValue implements ValidationRule
{
    public function __construct(private readonly int $maxLength = 255) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value) || is_object($value)) {
            $fail('Los valores de extra deben ser simples (número, texto o booleano).');

            return;
        }

        if (is_string($value) && mb_strlen($value) > $this->maxLength) {
            $fail('Cada valor de extra no puede superar los '.$this->maxLength.' caracteres.');
        }
    }
}
