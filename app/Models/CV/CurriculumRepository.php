<?php

namespace App\Models\CV;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CurriculumRepository.
 */
class CurriculumRepository extends Model
{
    protected $table = 'cv_repositories';

    protected $guarded = [
        'id'
    ];

    /**
     * Relaciona con el curriculum.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id', 'id');
    }

    /**
     * Relación con la imagen asociada al curriculum.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
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
     * Asociación con el tipo de repositorio.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(CurriculumAvailableRepositoryType::class, 'repository_type_id', 'id');
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
}
