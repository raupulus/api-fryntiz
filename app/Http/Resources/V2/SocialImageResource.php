<?php

declare(strict_types=1);

namespace App\Http\Resources\V2;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una imagen con lo que hace falta para las etiquetas Open Graph.
 *
 * B9: `og:image:type`, `og:image:width`, `og:image:height` y `twitter:image`
 * salían vacías en todas las páginas de contenido, y por eso al compartir un
 * enlace no aparecía la imagen. Los datos estaban en la tabla `files` desde
 * siempre —ancho, alto y tipo mime—; sólo había que leerlos.
 *
 * Si algún dato falta, la clave no se envía: es preferible omitir la etiqueta a
 * mandarla vacía, que es lo que confunde a los rastreadores.
 *
 * @mixin File
 */
class SocialImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_filter([
            'url' => $this->url,
            'width' => $this->width,
            'height' => $this->height,
            'type' => $this->fileType?->mime,
            'alt' => $this->alt ?? $this->name ?? null,
            'thumbnails' => array_filter([
                'micro' => $this->thumbnail('micro'),
                'small' => $this->thumbnail('small'),
                'medium' => $this->thumbnail('medium'),
                'large' => $this->thumbnail('large'),
            ]),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
