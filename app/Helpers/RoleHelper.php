<?php
/**
 * Created by PhpStorm.
 * User: Raúl Caro
 * Date: 24/06/2019
 * Time: 09:20
 */

use App\User;

/**
 * Class RoleHelper
 *
 * Gestión de permisos y comprobaciones de roles para usuarios.
 *
 * Los roles principales existentes son los siguientes:
 * 1 - SuperAdmin → Administrador omnipotente con superpoderes.
 * 2 - Admin → Administrador del sitio sin superpoderes.
 * 3 - Usuario Normal → Permisos generales.
 */
class RoleHelper
{
    /**
     * Array de Roles establecidos. Todo → Dinamizar desde intranet
     */
    private const SUPERADMIN = [1];
    private const ADMIN = [1,2];
    private const USER = [1,2,3];

    /**
     * Comprueba si el usuario actual es superusuario.
     *
     * @return bool
     */
    public static function isSuperAdmin($role_id = null)
    {
        $role_id = $role_id ?: auth()->user()->role_id;
        return in_array($role_id, self::SUPERADMIN, false);
    }

    /**
     * Comprueba si el usuario actual o el recibido tiene permisos de
     * administración.
     *
     * @return bool
     */
    public static function isAdmin($role_id = null)
    {
        $role_id = $role_id ?: auth()->user()->role_id;
        return in_array($role_id, self::ADMIN, false);
    }

    /**
     * Comprueba si el usuario actual no es administrador
     *
     * @return bool
     */
    public static function notIsAdmin($role_id = null)
    {
        return !self::isAdmin($role_id);
    }

    /**
     * Comprueba si el usuario actual puede editar el usuario.
     *
     * @param null $user_id
     *
     * @return bool
     */
    public static function canUserEdit($edit_user_id = null)
    {
        $role_id = auth()->user()->role_id;
        $user_id = auth()->id();

        return self::isAdmin(
            $role_id) ||
            ($edit_user_id && ($user_id === $edit_user_id)
        );
    }

    /**
     * Comprueba si el usuario puede crear usuarios.
     *
     * @param null $user_id
     *
     * @return bool
     */
    public static function canUserCreate($user_id = null)
    {
        $role_id = auth()->user()->role_id;
        return self::isAdmin($role_id);
    }

    /**
     * Comprueba si el usuario puede ver al usuario solicitado.
     *
     * @param null $view_user_id
     *
     * @return bool
     */
    public static function canUserView($view_user_id = null)
    {
        $role_id = auth()->user()->role_id;

        if (self::isAdmin(($role_id))) {
            return true;
        }

        $user_id = auth()->id();
        $user_view = User::find($view_user_id);

        if ($user_view) {
            return ($user_id === $view_user_id) || (! self::isAdmin($user_view->role_id));
        }

        return false;
    }

    /**
     * Comprueba si puede borrar al usuario.
     *
     * @param null $delete_user_id
     *
     * @return bool
     */
    public static function canUserDelete($delete_user_id = null)
    {
        $role_id = auth()->user()->role_id;
        $user_id = auth()->id();

        return self::isAdmin($role_id) || ($user_id === $delete_user_id);
    }
}
