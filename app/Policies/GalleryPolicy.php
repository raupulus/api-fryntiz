<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Model;

/**
 * Galerías y las imágenes que cuelgan de ellas.
 *
 * La imagen no tiene dueño propio: lo hereda de su galería, que es donde consta
 * quién la creó.
 */
class GalleryPolicy extends OwnedResourcePolicy
{
    protected function ownerId(Model $model): ?int
    {
        if ($model instanceof GalleryImage) {
            $model = $model->gallery;
        }

        if (! $model instanceof Gallery) {
            return null;
        }

        return $model->user_id === null ? null : (int) $model->user_id;
    }
}
