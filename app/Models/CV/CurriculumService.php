<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 *
 * @property int $id
 * @property int $curriculum_id Relación con el curriculum
 * @property int|null $image_id Relación con la imagen asociada
 * @property string $name Nombre del servicio
 * @property string|null $url URL hacia el servicio
 * @property string|null $description Descripción del servicio
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CV\Curriculum $curriculum
 * @property-read string $url_image
 * @property-read \App\Models\File|null $image
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumService whereUrl($value)
 * @mixin \Eloquent
 */
class CurriculumService extends CurriculumBaseSection
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
        'index' => 'dashboard.curriculums.services.index',
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_services';

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
     * @return string[][]
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
