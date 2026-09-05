<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Autorización sobre plantas de SmartPlant.
 *
 * Estaba vacía (sólo el constructor generado). Como `smartplant_registers` no
 * tiene columna `user_id` (N288), la planta es el único sitio donde consta de
 * quién es una lectura: si la propiedad de la planta no se comprueba, no se
 * comprueba nada.
 */
class SmartPlantPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SmartPlantPlant $plant): bool
    {
        return $this->isOwnedBy($user, $plant);
    }

    public function create(User $user): bool
    {
        return ! TokenAbilities::deviceRequest($user);
    }

    public function update(User $user, SmartPlantPlant $plant): bool
    {
        return $this->isOwnedBy($user, $plant);
    }

    public function delete(User $user, SmartPlantPlant $plant): bool
    {
        return ! TokenAbilities::deviceRequest($user)
            && ((int) $plant->user_id === (int) $user->id || $user->isAdmin());
    }

    public function restore(User $user, SmartPlantPlant $plant): bool
    {
        return $this->delete($user, $plant);
    }

    public function forceDelete(User $user, SmartPlantPlant $plant): bool
    {
        return $this->delete($user, $plant);
    }

    /**
     * Escribir una lectura contra esta planta.
     */
    public function writeData(User $user, SmartPlantPlant $plant): bool
    {
        return $this->isOwnedBy($user, $plant);
    }

    /**
     * Pertenencia de la planta.
     *
     * Esto comprobaba antes que, si el token venía ligado a un dispositivo, la
     * planta colgara de ese dispositivo — y si no colgaba de ninguno, exigía
     * que la petición NO fuese de un cacharro.
     *
     * El problema: `smartplant_plants` no tiene columna `hardware_device_id`.
     * La planta se relaciona con el hardware a través de sus lecturas, no
     * directamente. Así que `$plant->hardware_device_id` era null SIEMPRE, se
     * entraba siempre por la rama de arriba, y como cualquier token con la
     * ability `smartplant:write` cuenta como token de dispositivo
     * (`TokenAbilities::isDeviceToken()`), la respuesta era siempre `false`.
     *
     * Efecto: `GET /smartplant/plants/{plant}/readings` devolvía 404 a todo el
     * mundo, incluido el dueño de la planta, mientras
     * `GET /smartplant/plants` —que filtra por `user_id` sin pasar por aquí—
     * sí listaba esas mismas plantas. Se podía ver la lista y no abrir ninguna.
     * Salió al escribir el test del endpoint (TES-01).
     *
     * La propiedad de una planta es su `user_id` y nada más. El alcance por
     * dispositivo se comprueba donde sí hay dispositivo: en las lecturas.
     *
     * ## El administrador también llega (AR-SEC-03)
     *
     * El atajo `Gate::before` de `AppServiceProvider` sólo cubre a `SuperAdmin`.
     * Un `Admin` legítimo no recibe nada de ahí, así que sin este añadido se
     * llevaba un 403 al abrir en `/admin` cualquier planta que no fuese suya:
     * veía el listado y no podía entrar en ninguna fila.
     *
     * El bypass es para administradores **con sesión**, nunca para tokens de
     * dispositivo. El dueño de los cacharros es `SuperAdmin`, de modo que un
     * `|| $user->isAdmin()` a secas —que es lo que pedía la auditoría— haría
     * que el token grabado en una placa alcanzara las plantas de todos los
     * demás usuarios: justo el agujero que `Gate::before` evita al devolver
     * `null` en peticiones de dispositivo.
     */
    private function isOwnedBy(User $user, SmartPlantPlant $plant): bool
    {
        if (TokenAbilities::deviceRequest($user)) {
            return (int) $plant->user_id === (int) $user->id;
        }

        return (int) $plant->user_id === (int) $user->id || $user->isAdmin();
    }
}
