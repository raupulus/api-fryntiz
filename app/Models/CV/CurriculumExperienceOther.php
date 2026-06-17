<?php

namespace App\Models\CV;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 *
 * @property int $id
 * @property int $curriculum_id Relación con el curriculum
 * @property int|null $image_id Relación con la imagen
 * @property string $title Título de la experiencia
 * @property string|null $position Puesto ocupado en la experiencia
 * @property string|null $company Empresa donde trabajó
 * @property string|null $description Descripción
 * @property string|null $note Notas
 * @property string|null $start_at Fecha de inicio
 * @property string|null $end_at Fecha de fin
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CV\Curriculum $curriculum
 * @property-read string $url_image
 * @property-read \App\Models\File|null $image
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceOther whereUpdatedAt($value)
 * @mixin \Eloquent
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
        'index' => 'dashboard.curriculums.experience-other.index',
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
            'Título' => 'title',
            // 'Descripción' => 'description',
            // 'Posición' => 'position',
            'Empresa' => 'company',
            // 'Notas' => 'note',
            'Inicio' => 'start_at',
            'Fin' => 'end_at',
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
            'title' => [
                'type' => 'text',
            ],
            'position' => [
                'type' => 'text',
            ],
            'company' => [
                'type' => 'text',
            ],
            'note' => [
                'type' => 'text',
            ],
            'start_at' => [
                'type' => 'date',
            ],
            'end_at' => [
                'type' => 'date',
            ],
            'description' => [
                'type' => 'text',
            ],
        ];
    }
}
