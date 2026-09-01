<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\Content\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource ligero para contenido relacionado en API V2.
 *
 * @mixin Content
 */
class ContentRelatedResource extends JsonResource
{
    /*
     * Estas relaciones se leen directamente y NO con `whenLoaded()`: quien use
     * este resource tiene que cargarlas con su `with()`.
     *
     * Es una decisión, no un olvido (API-05). `whenLoaded()` haría DESAPARECER
     * la clave del JSON cuando la relación no viene cargada, que es un fallo
     * más silencioso que el que evita: hoy, sin eager load, salta
     * `preventLazyLoading` en local y se ve enseguida. Todos los llamantes
     * actuales cargan lo que hace falta y no hay N+1 real.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'image' => $this->image,
            'type' => $this->type,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
