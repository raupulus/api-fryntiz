<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\SmartPlant\SmartPlantPlant;
use App\Support\Auth\TokenAbilities;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function auth;

/**
 * Verifica que la planta indicada en una escritura de SmartPlant pertenece al
 * usuario autenticado.
 *
 * V1 comprobaba la propiedad de la planta y v2 dejó de hacerlo (**H5**):
 * `plant_id` sólo llevaba `exists:smartplant_plants,id`, así que cualquiera con
 * la ability `smartplant:write` podía escribir lecturas en la planta de otro.
 *
 * Y no había ninguna otra red: `smartplant_registers` **no tiene columna
 * `user_id`** (**N288**), así que la única forma de saber de quién es una
 * lectura es a través de su planta.
 *
 * La existencia se delega en la regla `exists` del FormRequest.
 */
class OwnedSmartPlant implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();

        if (! $user) {
            $fail('No estás autenticado para escribir lecturas de plantas.');

            return;
        }

        if (! is_numeric($value)) {
            return;
        }

        $plant = SmartPlantPlant::query()->find((int) $value);

        if (! $plant) {
            return;
        }

        if ((int) $plant->user_id !== (int) $user->id) {
            $fail('La planta indicada no pertenece a tu cuenta.');

            return;
        }

        // Ligado estricto: un token emitido para el dispositivo X no escribe en
        // una planta que cuelga del dispositivo Y, aunque ambos sean del mismo
        // dueño.
        if ($plant->hardware_device_id !== null
            && ! TokenAbilities::tokenReachesDevice($user, (int) $plant->hardware_device_id)) {
            $fail('El token utilizado no está autorizado para la planta indicada.');
        }
    }
}
