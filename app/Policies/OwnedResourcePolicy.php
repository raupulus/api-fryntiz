<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base de las policies de recursos que **tienen dueño**: impresoras, galerías,
 * sistemas de energía, rutas de vuelo.
 *
 * Todas dicen lo mismo con distinta columna: el registro es de quien lo creó, y
 * un administrador humano llega a todos. La única diferencia entre ellas es de
 * dónde sale el `user_id`, así que eso es lo único que declara cada hija en
 * {@see self::ownerId()}.
 *
 * Nace de AR-SEC-01: estos modelos no tenían policy y Filament, ante un modelo
 * sin policy, autoriza **todo**. Un `Editor` podía borrar impresoras y sistemas
 * solares ajenos desde `/admin`.
 *
 * ## Por qué el administrador no entra por `Gate::before`
 *
 * El atajo global de `AppServiceProvider` sólo cubre a `SuperAdmin`, y a
 * propósito se desactiva para peticiones de dispositivo. Un `Admin` legítimo no
 * recibe nada de ahí, de modo que si la comprobación de propiedad no lo
 * contempla se queda fuera de su propio panel (AR-SEC-03).
 *
 * ## Por qué el bypass de administrador excluye a los tokens IoT
 *
 * El dueño de los cacharros es `SuperAdmin`. Un `|| $user->isAdmin()` a secas
 * haría que el token grabado en una placa —cuyo usuario asociado es ese
 * administrador— heredase acceso a los recursos de todos los demás, que es
 * justo lo que `Gate::before` evita al devolver `null` en peticiones de
 * dispositivo. El bypass es para administradores **con sesión**.
 */
abstract class OwnedResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Identificador del propietario del registro, o `null` si no consta.
     *
     * Un registro sin dueño no es de nadie: sólo lo alcanza un administrador.
     */
    abstract protected function ownerId(Model $model): ?int;

    public function viewAny(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->alcanza($user, $model);
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->alcanza($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->alcanza($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->alcanza($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    /**
     * Es suyo, o quien pregunta es un administrador con sesión.
     */
    protected function alcanza(User $user, Model $model): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return false;
        }

        $propietario = $this->ownerId($model);

        if ($propietario !== null && $propietario === (int) $user->id) {
            return true;
        }

        return $user->isAdmin();
    }
}
