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

    /**
     * Roles que puede asignar quien tiene este rol.
     *
     * Nadie reparte un rol por encima del suyo. Sin esto, un `Admin` podía
     * ponerse `SuperAdmin` a sí mismo desde `/admin/users/{id}/edit` —o dar de
     * alta un `SuperAdmin` nuevo con una contraseña que él elegía— y quedarse
     * con el bypass total de `Gate::before` (auditoría AR-P01, reproducido).
     *
     * `SuperAdmin` es el único que puede crear otro `SuperAdmin`. Un `Admin`
     * llega hasta `Admin`: repartir su mismo nivel no es escalar.
     *
     * `User` y `Editor` no reparten nada. Hoy ni siquiera alcanzan el recurso
     * de usuarios (`UserPolicy::viewAny()` exige `isAdmin()`), pero la lista se
     * define aquí para que el día que eso cambie no se abra sola.
     *
     * @return list<self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::SuperAdmin => self::cases(),
            self::Admin => [self::Admin, self::User, self::Editor],
            self::User, self::Editor => [],
        };
    }

    /**
     * ¿Quien tiene este rol puede asignar el rol indicado?
     */
    public function canAssign(self|int $role): bool
    {
        $role = $role instanceof self ? $role : self::tryFrom($role);

        return $role !== null && in_array($role, $this->assignableRoles(), true);
    }

    /**
     * Ids de los roles asignables, para las opciones de un `Select`.
     *
     * @return list<int>
     */
    public function assignableRoleIds(): array
    {
        return array_map(static fn (self $role): int => $role->value, $this->assignableRoles());
    }
}
