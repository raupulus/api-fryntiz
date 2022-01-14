<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 */
class CurriculumExperienceAdditional extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Experiencia adicional';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Experiencias adicionales';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_experience_additional';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.experience_additional.edit',
        'delete' => 'dashboard.cv.experience_additional.destroy',
        'destroy' => 'dashboard.cv.experience_additional.destroy',
        'store' => 'dashboard.cv.experience_additional.store',
        'update' => 'dashboard.cv.experience_additional.update',
        'index' => 'dashboard.cv.experience_additional.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.experience-additional.index'
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_experience_additional';

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
