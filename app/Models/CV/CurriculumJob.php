<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 */
class CurriculumJob extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Trabajo';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Trabajos';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_jobs';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.job.edit',
        'delete' => 'dashboard.cv.job.destroy',
        'destroy' => 'dashboard.cv.job.destroy',
        'store' => 'dashboard.cv.job.store',
        'update' => 'dashboard.cv.job.update',
        'index' => 'dashboard.cv.job.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.job.index'
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_jobs';

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
            'Descripción' => 'description',
            'URL' => 'url',
            'URL Info' => 'urlinfo',
            'Repositorio' => 'repository',
            'Role' => 'role'
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
            'description' => [
                'type' => 'text',
            ],
            'url' => [
                'type' => 'link',
            ],
            'urlinfo' => [
                'type' => 'link',
            ],
            'repository' => [
                'type' => 'link',
            ],
            'role' => [
                'type' => 'badge',
            ],
        ];
    }
}
