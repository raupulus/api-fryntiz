<?php

namespace App\Models\CV;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 */
class CurriculumService extends Model
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Servicio';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Servicios';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_services';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.service.edit',
        'delete' => 'dashboard.cv.service.destroy',
        'destroy' => 'dashboard.cv.service.destroy',
        'store' => 'dashboard.cv.service.store',
        'update' => 'dashboard.cv.service.update',
        'index' => 'dashboard.cv.service.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.services.index'
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_services';

    /**
     * @var string[] Campos que se pueden llenar mediante el uso de mass-assignment.
     */
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
     * Elimina de forma segura un repositorio y los datos asociados.
     *
     * @return bool
     */
    public function safeDelete()
    {
        ## Elimino la imagen asociada al curriculum y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }
}
