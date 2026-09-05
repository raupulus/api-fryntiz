<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogos globales del sistema: tipos de fichero, tipos de hardware,
 * componentes disponibles, tipos de repositorio de CV, tipos de impresora.
 *
 * No tienen dueño: son las listas de las que tira todo el mundo. Quien las
 * toca cambia el vocabulario de la aplicación entera, así que sólo un
 * administrador entra.
 *
 * Existe por AR-SEC-01: estos modelos no tenían policy, y en Filament un
 * modelo sin policy es un modelo **sin restricciones** — `Gate::getPolicyFor()`
 * devuelve null y el recurso autoriza todas las acciones. Se comprobó con un
 * `Editor` autenticado: veía y creaba en todos ellos.
 *
 * Se registra una sola vez para varios modelos, igual que
 * {@see WeatherStationPolicy}: comparten criterio, no hace falta una clase por
 * tabla.
 */
class AdminCatalogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->esAdministrador($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->esAdministrador($user);
    }

    public function create(User $user): bool
    {
        return $this->esAdministrador($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->esAdministrador($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->esAdministrador($user);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->esAdministrador($user);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isSuperAdmin() && ! TokenAbilities::deviceRequest($user);
    }

    /**
     * Administrador **humano**.
     *
     * El matiz del token importa: el dueño de los cacharros es SuperAdmin, así
     * que sin descartar las peticiones de dispositivo el token grabado en una
     * estación meteorológica heredaría permiso para reescribir los catálogos.
     */
    private function esAdministrador(User $user): bool
    {
        return $user->isAdmin() && ! TokenAbilities::deviceRequest($user);
    }
}
