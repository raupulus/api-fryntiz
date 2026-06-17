<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\BaseModels\BaseModel;
use App\Models\File;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Content\ContentAvailableType
 *
 * @property int $id
 * @property int|null $file_id FK a la imagen en la tabla files
 * @property string $name Nombre del tipo de contenido
 * @property string $plural_name Nombre en plural para los tipos de contenido
 * @property string $slug Slug para el URL
 * @property string|null $description Descripción acerca del tipo de contenido
 * @property string|null $icon Clase css para el icono
 * @property string $color Código Hexadecimal del color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Collection<int, Content> $contents
 * @property-read int|null $contents_count
 * @property-read Collection<int, Content> $contentsActive
 * @property-read int|null $contents_active_count
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType wherePluralName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentAvailableType whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ContentAvailableType extends BaseModel
{
    protected $table = 'content_available_types';

    protected $fillable = ['name', 'plural_name', 'slug', 'description'];

    /**
     * Relación con la imagen principal asociada al tipo de contenido.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /**
     * Relación con los contenidos que utilizan este tipo de contenido.
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'type_id', 'id');
    }

    /**
     * Relación con los contenidos activos que utilizan este tipo de contenido.
     */
    public function contentsActive(): HasMany
    {
        return $this->contents()
            ->where('is_active', true)
            ->whereNotNull('published_at');
    }

    /**
     * Obtenemos las estadísticas para un tipo de contenido concreto con base a la plataforma recibida.
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
