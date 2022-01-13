<?php

namespace App\Models\CV;

/**
 * Class CurriculumRepository.
 */
class CurriculumRepository extends CurriculumBaseSection
{

    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Repositorio';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Repositorios';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_repository';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.repository.edit',
        'delete' => 'dashboard.cv.repository.destroy',
        'destroy' => 'dashboard.cv.repository.destroy',
        'store' => 'dashboard.cv.repository.store',
        'update' => 'dashboard.cv.repository.update',
        'index' => 'dashboard.cv.repository.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.repositories.index'
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_repositories';

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'Imagen' => 'image',
            'Título' => 'title',
            'Tipo' => 'type',
            'URL' => 'url',
            'Descripción' => 'description',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    public static function getTableCellsInfo()
    {
        return [
            'image' => [
                'type' => 'image',
                'thumbnail' => true,
                'thumbnail_size' => 'medium',
            ],
            'title' => [
                'type' => 'text',
            ],
            'type' => [
                'relation' => true,  // Indica que es una relación
                'relation_field' => 'name',  // Indica el atributo de la relación
                'type' => 'text',
                'wrapper' => '<span class="badge badge-secondary">{{value}}</span>',
            ],
            'url' => [
                'type' => 'link',
            ],
            'description' => [
                'type' => 'text',
            ],
        ];
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
}
