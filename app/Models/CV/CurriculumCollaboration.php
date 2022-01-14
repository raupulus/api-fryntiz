<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 */
class CurriculumCollaboration extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Colaboración';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Colaboraciones';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_collaboration';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.collaboration.edit',
        'delete' => 'dashboard.cv.collaboration.destroy',
        'destroy' => 'dashboard.cv.collaboration.destroy',
        'store' => 'dashboard.cv.collaboration.store',
        'update' => 'dashboard.cv.collaboration.update',
        'index' => 'dashboard.cv.collaboration.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.collaboration.index'
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_collaborations';

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
        ];
    }
}
