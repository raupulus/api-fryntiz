<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un colaborador: sólo nombre, apodo y foto de perfil (null si no tiene),
 * nunca su email.
 *
 * @mixin User
 */
class ContentContributorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->fullName,
            'nick' => $this->nickname,
            'image' => $this->photoUrl(),
        ];
    }
}
