<?php

declare(strict_types=1);

namespace App\Http\Resources\V2;

use App\Models\ApiToken;
use App\Support\Auth\TokenAbilities;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un token de la API, sin el token.
 *
 * El valor en claro sólo existe en la respuesta que lo crea. Aquí no se expone
 * ni el hash: no sirve para nada al cliente y es material sensible.
 *
 * @mixin ApiToken
 */
class ApiTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $abilities = (array) ($this->abilities ?? []);
        $devices = TokenAbilities::devicesOf($abilities);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'abilities' => array_values($abilities),
            // Se saca aparte porque es lo que responde a «¿a qué cacharro
            // pertenece este token?», que es la pregunta que se hace uno al
            // mirar la lista.
            'device_ids' => $devices,
            'is_device_token' => $devices !== [] || TokenAbilities::isDeviceToken($abilities),
            'last_used_at' => $this->last_used_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->expires_at !== null && $this->expires_at->isPast(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
