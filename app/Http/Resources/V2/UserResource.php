<?php

declare(strict_types=1);

namespace App\Http\Resources\V2;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->when($this->isOwner($request), $this->email),
            // `role` puede no estar cargada. Con `preventLazyLoading` activo
            // fuera de producción, `$this->role->name` reventaba con una
            // LazyLoadingViolationException; en producción hacía una consulta
            // extra por cada usuario del listado (fix1 #14).
            'role' => $this->whenLoaded('role', fn () => $this->role->name),
            'email_verified' => $this->email_verified_at !== null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * El email sólo lo ve su dueño o un administrador.
     */
    private function isOwner(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && ((int) $user->id === (int) $this->id || $user->isAdmin());
    }
}
