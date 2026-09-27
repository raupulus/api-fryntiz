<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Content;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un fichero del contenido (lo subido desde el editor: imágenes y adjuntos).
 * Necesita `fileType`.
 *
 * @mixin File
 */
class ContentFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name ?? $this->name,
            'title' => $this->title,
            'alt' => $this->alt,
            'type' => $this->fileType?->mime,
            'is_image' => $this->fileType?->type === 'image',
            'size' => $this->size === null ? null : (int) $this->size,
            'width' => $this->width === null ? null : (int) $this->width,
            'height' => $this->height === null ? null : (int) $this->height,
            'url' => $this->url,
            'download_url' => route('file.download', ['module' => $this->module, 'id' => $this->id, 'slug' => $this->name]),
        ];
    }
}
