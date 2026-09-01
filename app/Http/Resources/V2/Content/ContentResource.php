<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Http\Resources\V2\SocialImageResource;
use App\Models\Content\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para contenido en API V2.
 *
 * @mixin Content
 */
class ContentResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            // Con ancho, alto y tipo mime, para que las webs puedan construir
            // las etiquetas Open Graph completas. Salían vacías y por eso al
            // compartir un enlace no aparecía la imagen (B9).
            'image' => $this->whenLoaded('image', fn () => new SocialImageResource($this->image)),
            // El SEO vive en la relación `seo` (tabla content_seo), no en
            // `contents`. Esto leía `$this->seo_title` y `$this->seo_description`,
            // que no existen ni como columna ni como accessor: la API prometía
            // metadatos SEO y devolvía null SIEMPRE. Las webs que consumen esto
            // los necesitan justo para construir sus meta tags.
            //
            // `og_title` es el título pensado para compartir; si no se ha
            // rellenado se cae al título del contenido, que es mejor que nada.
            'seo_title' => $this->seo?->og_title ?: $this->title,
            'seo_description' => $this->seo?->description ?: $this->excerpt,
            'platform' => $this->whenLoaded('platform', fn () => [
                'id' => $this->platform->id,
                'name' => $this->platform->title,
                'slug' => $this->platform->slug,
            ]),
            'categories' => $this->whenLoaded('categories'),
            'tags' => $this->whenLoaded('tags'),
            'technologies' => $this->whenLoaded('technologies'),
            'pages_count' => $this->whenCounted('pages'),
            // `withSum` devuelve null cuando el contenido no tiene ninguna
            // visita todavía, no 0.
            'views_count' => (int) ($this->views_count ?? 0),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
