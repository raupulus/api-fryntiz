<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum para los roles de usuario del sistema.
 * Los valores corresponden a los IDs en la tabla user_roles.
 */
enum UserRoleEnum: int
{
    case SuperAdmin = 1;
    case Admin = 2;
    case User = 3;
    case Editor = 4;

    /**
     * Obtener la etiqueta en español del rol.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrador',
            self::Admin => 'Administrador',
            self::User => 'Usuario',
            self::Editor => 'Editor',
        };
    }
}
