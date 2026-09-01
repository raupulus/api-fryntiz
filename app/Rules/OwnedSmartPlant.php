<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\SmartPlant\SmartPlantPlant;
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

        // Aquí había un "ligado estricto" que pretendía impedir que un token
        // emitido para el dispositivo X escribiera en una planta colgada del
        // dispositivo Y. No hacía nada: leía `$plant->hardware_device_id`, y
        // `smartplant_plants` NO tiene esa columna —una planta se relaciona con
        // el hardware a través de sus lecturas, no directamente—, así que el
        // valor era siempre null y la condición nunca se cumplía.
        //
        // Se retira en lugar de dejarlo: un bloque que aparenta una protección
        // que no existe es peor que no tener el bloque, porque quien lo lee da
        // por hecho que el caso está cubierto. Es el mismo despiste que había
        // en SmartPlantPolicy::isOwnedBy(), y PHPStan lo señalaba en los dos
        // sitios; estaba silenciado en el baseline.
        //
        // Lo que esta regla SÍ garantiza es lo que importa y lo que motivó su
        // existencia (H5): que no se escriban lecturas en la planta de otro
        // usuario. Para acotar además por dispositivo haría falta una columna
        // que ligue planta y hardware, y hoy no la hay.
    }
}
