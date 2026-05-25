<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Trait para modelos que tienen un campo slug.
 * Genera automáticamente el slug a partir del título si no se proporciona.
 */
trait HasSlug
{
    /**
     * Inicializar el trait: generar slug automáticamente al crear.
     */
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title ?? $model->name);
            }
        });
    }

    /**
     * Scope para buscar por slug.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Obtener la clave de ruta del modelo.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
