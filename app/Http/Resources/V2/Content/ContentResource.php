<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Http\Resources\V2\SocialImageResource;
use App\Models\Content\Content;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Los datos de un contenido, sin sus partes: lo que lleva cada elemento del
 * listado y la base del detalle (`ContentDetailResource`, F9 del plan de
 * contenidos del 2026-09-24).
 *
 * Lee sus relaciones directamente, sin `whenLoaded()` (D9): quien lo use carga
 * `type`, `status`, `image.fileType`, `image.thumbnails`, `platform` y `seo`, y
 * los agregados `pages_count` y `views_count`. Si falta algo, salta
 * `preventLazyLoading` fuera de producción, en vez de desaparecer una clave.
 *
 * @mixin Content
 */
class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            // Compactos: antes salía la fila entera de la tabla, con `file_id`,
            // `icon`, `color` y fechas.
            'type' => $this->type === null ? null : [
                'id' => $this->type->id,
                'slug' => $this->type->slug,
                'name' => $this->type->name,
                'plural_name' => $this->type->plural_name,
            ],
            'status' => $this->status === null ? null : [
                'id' => $this->status->id,
                'slug' => $this->status->slug,
                'name' => $this->status->name,
            ],
            'is_featured' => (bool) $this->is_featured,
            // Con ancho, alto, tipo y todos los tamaños, para las etiquetas
            // Open Graph (B9).
            'image' => $this->image === null ? null : new SocialImageResource($this->image),
            // `og_title` es el pensado para compartir; si no se ha rellenado se
            // cae al título. El SEO completo va en `?include=seo`.
            'seo_title' => $this->seo?->og_title ?: $this->title,
            'seo_description' => $this->seo?->description ?: $this->excerpt,
            'platform' => [
                'id' => $this->platform->id,
                'slug' => $this->platform->slug,
                'title' => $this->platform->title,
            ],
            // Dónde enseña cada web el contenido (E7): lo decide cada web.
            'visibility' => [
                'home' => (bool) $this->is_visible_on_home,
                'menu' => (bool) $this->is_visible_on_menu,
                'footer' => (bool) $this->is_visible_on_footer,
                'sidebar' => (bool) $this->is_visible_on_sidebar,
                'search' => (bool) $this->is_visible_on_search,
                'archive' => (bool) $this->is_visible_on_archive,
                'rss' => (bool) $this->is_visible_on_rss,
                'sitemap' => (bool) $this->is_visible_on_sitemap,
                'sitemap_news' => (bool) $this->is_visible_on_sitemap_news,
            ],
            // Aún no hay comentarios, pero van en el contrato (E7).
            'comments' => [
                'enabled' => (bool) $this->is_comment_enabled,
                'anonymous' => (bool) $this->is_comment_anonymous,
            ],
            // null = no se ha comprobado.
            'copyright_valid' => $this->is_copyright_valid === null ? null : (bool) $this->is_copyright_valid,
            'pages_count' => (int) ($this->pages_count ?? 0),
            // `withSum` devuelve null sin visitas, no 0.
            'views_count' => (int) ($this->views_count ?? 0),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
