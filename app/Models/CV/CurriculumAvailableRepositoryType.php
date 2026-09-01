<?php

declare(strict_types=1);

namespace App\Models\CV;

use App\Models\BaseModels\BaseModel;
use App\Models\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

use function func_get_args;
use function is_array;

/**
 * Class CurriculumAvailableRepositoryType
 *
 * @property int $id
 * @property int|null $image_id Relación con la imagen asociada
 * @property string|null $title Título para el repositorio
 * @property string|null $name Nombre del repositorio
 * @property string $slug Identificador único para el repositorio
 * @property string|null $url Dirección al repositorio
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $url_image
 * @property-read File|null $image
 *
 * @method static Builder<static>|CurriculumAvailableRepositoryType newModelQuery()
 * @method static Builder<static>|CurriculumAvailableRepositoryType newQuery()
 * @method static Builder<static>|CurriculumAvailableRepositoryType query()
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereCreatedAt($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereId($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereImageId($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereName($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereSlug($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereTitle($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereUpdatedAt($value)
 * @method static Builder<static>|CurriculumAvailableRepositoryType whereUrl($value)
 *
 * @mixin \Eloquent
 */
class CurriculumAvailableRepositoryType extends BaseModel
{
    protected $table = 'cv_available_repository_types';

    /**
     * @var string[] Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    protected $fillable = [
        'image_id',
        'title',
        'name',
        'slug',
        'url',
    ];

    /**
     * Relación con la imagen asociada al tipo de repositorio.
     */
    public function image(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }

    /**
     * Devuelve la ruta hacia la foto asociada al curriculum.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        return $this->image ? $this->image->url : File::urlDefaultImage('large');
    }

    /**
     * Devuelve el thumbnail de la imagen asociada.
     *
     *
     * @return mixed
     */
    public function urlThumbnail($size = 'medium')
    {
        if ($this->image) {
            return $this->image->thumbnail($size);
        }

        return File::urlDefaultImage($size);
    }

    /**
     * Elimina de forma segura un tipo de repositorio y los datos asociados.
     */
    public function safeDelete(): bool
    {
        // # Elimino la imagen asociada al tipo de repositorio y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }

    /**
     * Devuelve todos los elementos filtrados y ordenados en una colección de
     * eloquent.
     *
     *
     * @return CurriculumAvailableRepositoryType[]|Builder[]|Collection
     */
    public static function all($columns = ['*'])
    {
        return static::query()
            ->orderBy('title')
            ->get(
                is_array($columns) ? $columns : func_get_args()
            );
    }
}
