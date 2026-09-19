<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Gallery;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait para entidades que pueden tener una o varias galerías de imágenes asociadas.
 */
trait HasGalleries
{
    /**
     * Galerías asociadas a este modelo.
     *
     * @return MorphToMany<Gallery, $this>
     */
    public function galleries(): MorphToMany
    {
        return $this->morphToMany(Gallery::class, 'galleryable', 'galleryables')
            ->withPivot(['order'])
            ->orderByPivot('order')
            ->withTimestamps();
    }
}
