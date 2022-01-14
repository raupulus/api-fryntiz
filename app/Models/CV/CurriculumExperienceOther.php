<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 */
class CurriculumExperienceOther extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Otra experiencia';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Otras experiencias';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_experience_others';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.experience_other.edit',
        'delete' => 'dashboard.cv.experience_other.destroy',
        'destroy' => 'dashboard.cv.experience_other.destroy',
        'store' => 'dashboard.cv.experience_other.store',
        'update' => 'dashboard.cv.experience_other.update',
        'index' => 'dashboard.cv.experience_other.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.experience-other.index'
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_experience_others';

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'Imagen' => 'image',
            'Nombre' => 'name',
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
            'name' => [
                'type' => 'text',
            ],
            'url' => [
                'type' => 'link',
            ],
            'description' => [
                'type' => 'text',
            ],
        ];
    }
}
