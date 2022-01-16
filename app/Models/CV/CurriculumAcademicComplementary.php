<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 */
class CurriculumAcademicComplementary extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Formación Académica Complementaria';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Formaciones Académicas Complementarias';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_academic_complementary';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.academic_complementary.edit',
        'delete' => 'dashboard.cv.academic_complementary.destroy',
        'destroy' => 'dashboard.cv.academic_complementary.destroy',
        'store' => 'dashboard.cv.academic_complementary.store',
        'update' => 'dashboard.cv.academic_complementary.update',
        'index' => 'dashboard.cv.academic_complementary.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.academic-complementary.index',
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_academic_complementary';

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
            'Entidad emisora' => 'credential_id',
            //'URL' => 'url',
            'Url' => 'credential_url',
            //'Descripción' => 'description',
            //'Notas' => 'note',
            //'Conocimientos adquiridos' => 'learned',
            'Horas' => 'hours',
            //'Instructor de la formación' => 'instructor',
            '¿Expira?' => 'expires',
            //'Fecha de expiración' => 'expires_at',
            'Expedido en' => 'expedition_at',
            //'Fecha de inicio' => 'start_at',
            //'Fecha de fin' => 'end_at',
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
            'credential_id' => [
                'type' => 'text',
            ],
            'url' => [
                'type' => 'link',
            ],
            'credential_url' => [
                'type' => 'link',
            ],
            'description' => [
                'type' => 'text',
            ],
            'note' => [
                'type' => 'text',
            ],
            'learned' => [
                'type' => 'text',
            ],
            'hours' => [
                'type' => 'text',
            ],
            'instructor' => [
                'type' => 'badge',
            ],
            'expires' => [
                'type' => 'boolean',
            ],
            'expires_at' => [
                'type' => 'datetime',
            ],
            'expedition_at' => [
                'type' => 'datetime',
            ],
            'start_at' => [
                'type' => 'datetime',
            ],
            'end_at' => [
                'type' => 'datetime',
            ],
        ];
    }
}
