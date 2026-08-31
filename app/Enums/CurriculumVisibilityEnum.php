<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Visibilidad de un currículum.
 *
 * Tres estados, no un booleano: la razón de tener varios CV es poder hacer uno
 * a medida de una oferta concreta y mandárselo a quien tú quieras **sin que
 * salga en internet ni lo indexe Google** (B1).
 */
enum CurriculumVisibilityEnum: string
{
    /** Sólo su dueño. */
    case Private = 'private';

    /** Quien tenga el enlace con el token. Se sirve con `noindex`. */
    case Shared = 'shared';

    /** Cualquiera, y aparece en el listado. */
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privado',
            self::Shared => 'Compartido por enlace',
            self::Public => 'Público',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Private => 'Sólo tú lo ves.',
            self::Shared => 'Lo ve quien tenga el enlace. No se indexa en buscadores ni sale en el listado.',
            self::Public => 'Lo ve cualquiera y aparece en el listado público.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $opciones = [];

        foreach (self::cases() as $caso) {
            $opciones[$caso->value] = $caso->label();
        }

        return $opciones;
    }
}
