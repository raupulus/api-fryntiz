<?php

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\File;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Content\ContentAvailableType
 */
class ContentAvailableType extends BaseModel
{
    protected $table = 'content_available_types';

    protected $fillable = ['name', 'plural_name', 'slug', 'description'];

    /**
     * Relación con la imagen principal asociada al tipo de contenido.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /**
     * Relación con los contenidos que utilizan este tipo de contenido.
     *
     * @return HasMany
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'type_id', 'id');
    }

    /**
     * Relación con los contenidos activos que utilizan este tipo de contenido.
     *
     * @return HasMany
     */
    public function contentsActive(): HasMany
    {
        return $this->contents()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ;
    }

    /**
     * Obtenemos las estadísticas para un tipo de contenido concreto con base a la plataforma recibida.
     *
     * @param Platform $platform
     * @return array
     */
    public function getStatsByPlatform(Platform $platform): array
    {
        $contentQuery = $this->contentsActive()->where('platform_id', $platform->id);

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'plural_name' => $this->plural_name,
            'description' => $this->description,
            'quantity' => $contentQuery->count(), // Cantidad de contenido de este tipo.
        ];
    }
}
