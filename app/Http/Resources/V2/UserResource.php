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
            'role' => $this->role->name,
            'email_verified' => ! is_null($this->email_verified_at),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function isOwner(Request $request): bool
    {
        return $request->user()?->id === $this->id || $request->user()?->isAdmin();
    }
}
