<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRoleEnum;
use App\Models\ApiToken;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Tokens de la API. La pieza más sensible del panel, y la que estaba abierta.
 *
 * `ApiToken` no tenía policy registrada, y en Filament un modelo sin policy no
 * queda cerrado: queda **abierto**. `Gate::getPolicyFor()` devuelve `null` y el
 * recurso autoriza todas las acciones a cualquiera que llegue al panel — y al
 * panel llega también el rol `Editor`. Reproducido con un `Editor` autenticado:
 * `canViewAny()` y `canCreate()` devolvían `true` (AR-SEC-01). Desde ahí se
 * podía listar los tokens de todo el mundo, emitir uno a nombre de quien fuera
 * y usar la acción en lote `revoke_user` para dejar sin tokens a todos los
 * administradores de golpe.
 *
 * El criterio es el mismo que ya aplica {@see UserPolicy}: **un administrador
 * llega a todo menos a lo de un `SuperAdmin`**. Aquí ese matiz no es cortesía,
 * es la diferencia entre administrar y escalar: emitir un token es repartir una
 * identidad, así que un `Admin` capaz de crear o revocar el token de un
 * `SuperAdmin` tendría por la puerta de atrás lo que
 * {@see UserRoleEnum::assignableRoles()} le niega por la de delante.
 *
 * `SuperAdmin` no aparece en estos métodos porque nunca llega: el atajo
 * `Gate::before` de `AppServiceProvider` le concede el paso antes — salvo que la
 * petición venga de un token de dispositivo, en cuyo caso sí evalúa esta policy
 * y la respuesta es que no. Un cacharro no administra tokens, ni el suyo.
 */
class ApiTokenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->administra($user);
    }

    public function view(User $user, ApiToken $token): bool
    {
        return $this->administra($user) && ! $this->esDeSuperAdmin($token);
    }

    public function create(User $user): bool
    {
        return $this->administra($user);
    }

    public function update(User $user, ApiToken $token): bool
    {
        return $this->view($user, $token);
    }

    public function delete(User $user, ApiToken $token): bool
    {
        return $this->view($user, $token);
    }

    public function deleteAny(User $user): bool
    {
        return $this->administra($user);
    }

    public function restore(User $user, ApiToken $token): bool
    {
        return $this->view($user, $token);
    }

    public function forceDelete(User $user, ApiToken $token): bool
    {
        return $this->view($user, $token);
    }

    /**
     * Administrador humano. El matiz del token importa: el dueño de los
     * cacharros es `SuperAdmin`, y sin descartar las peticiones de dispositivo
     * el token grabado en una placa acabaría pudiendo emitir tokens nuevos.
     */
    private function administra(User $user): bool
    {
        return $user->isAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    /**
     * ¿El token pertenece a un `SuperAdmin`?
     *
     * Sólo un `SuperAdmin` toca los tokens de otro `SuperAdmin`, y ése no pasa
     * por aquí porque `Gate::before` lo resuelve antes.
     */
    private function esDeSuperAdmin(ApiToken $token): bool
    {
        if ($token->tokenable_type !== User::class) {
            return false;
        }

        $tokenable = $token->tokenable;

        return $tokenable instanceof User && $tokenable->isSuperAdmin();
    }
}
