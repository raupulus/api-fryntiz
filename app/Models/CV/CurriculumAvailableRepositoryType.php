<?php

namespace App\Models\CV;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use function func_get_args;
use function is_array;

/**
 * Class CurriculumAvailableRepositoryType
 */
class CurriculumAvailableRepositoryType extends Model
{
    protected $table = 'cv_available_repository_types';

    protected $guarded = [
        'id'
    ];

    /**
     * Relación con la imagen asociada al tipo de repositorio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function image()
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
     * @param $size
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
     *
     * @return bool
     */
    public function safeDelete()
    {
        ## Elimino la imagen asociada al tipo de repositorio y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }

    /**
     * Devuelve todos los elementos filtrados y ordenados en una colección de
     * eloquent.
     *
     * @param $columns
     *
     * @return \App\Models\CV\CurriculumAvailableRepositoryType[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
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
