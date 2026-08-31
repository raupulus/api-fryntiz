<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rechaza un sensor que no esté en el mapa de la estación meteorológica.
 *
 * El servicio resolvía el modelo por su cuenta y devolvía `null` para los
 * nombres que no conocía; el bucle seguía como si nada, el lote se aceptaba con
 * un 201 y ese sensor no se guardaba en ningún sitio (**N287**). Un nombre mal
 * escrito en el firmware se perdía sin decir nada.
 *
 * Los nombres válidos salen ahora de `SensorCatalog`, que es la única
 * lista que hay: el mapa duplicado del servicio se ha borrado.
 */
class KnownSensor implements ValidationRule
{
    /**
     * @param  list<string>  $known
     */
    public function __construct(private array $known) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sensor = str_contains($attribute, '.')
            ? substr($attribute, (int) strrpos($attribute, '.') + 1)
            : $attribute;

        if (! in_array($sensor, $this->known, true)) {
            $fail(sprintf(
                'El sensor «%s» no existe. Los válidos son: %s.',
                $sensor,
                implode(', ', $this->known)
            ));
        }
    }
}
