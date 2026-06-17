<?php

declare(strict_types=1);

namespace App\Models\CV;

use App\Models\File;
use Illuminate\Support\Carbon;

/**
 * Class CurriculumService
 *
 * Representa los servicios del usuario asociados a un curriculum.
 *
 * @property int $id
 * @property int $curriculum_id Relación con el curriculum
 * @property int|null $image_id Relación con la imagen
 * @property string $title Título obtenido
 * @property string|null $entity Entidad o empresa emisora
 * @property string|null $credential_id Identificador de la Credencial obtenida
 * @property string|null $credential_url Url hacia la Credencial obtenida
 * @property string|null $learned Conocimientos adquiridos
 * @property string|null $description Descripción
 * @property string|null $note Notas
 * @property int|null $hours Horas de formación
 * @property string|null $instructor Instructor de la formación
 * @property bool $expires ¿Expira la validez?
 * @property string|null $expires_at Fecha de expiración
 * @property string|null $expedition_at Fecha de expedición
 * @property string|null $start_at Fecha de inicio
 * @property string|null $end_at Fecha de fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Curriculum $curriculum
 * @property-read string $url_image
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereCredentialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereCredentialUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereEntity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereExpeditionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereExpires($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereInstructor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereLearned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicTraining whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CurriculumAcademicTraining extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Formación Académica';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Formaciones Académicas';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_academic_training';

    /**
     * @var string[] Rutas de acción para el dashboard sobre este modelo.
     */
    public static $routesDashboard = [
        'edit' => 'dashboard.cv.academic_training.edit',
        'delete' => 'dashboard.cv.academic_training.destroy',
        'destroy' => 'dashboard.cv.academic_training.destroy',
        'store' => 'dashboard.cv.academic_training.store',
        'update' => 'dashboard.cv.academic_training.update',
        'index' => 'dashboard.cv.academic_training.index',
    ];

    /**
     * Vistas para este modelo.
     *
     * @var string[]
     */
    public static $viewsDashboard = [
        'index' => 'dashboard.curriculums.academic.index',
    ];

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_academic_training';

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
            // 'URL' => 'url',
            'Url' => 'credential_url',
            // 'Descripción' => 'description',
            // 'Notas' => 'note',
            // 'Conocimientos adquiridos' => 'learned',
            'Horas' => 'hours',
            // 'Instructor de la formación' => 'instructor',
            '¿Expira?' => 'expires',
            // 'Fecha de expiración' => 'expires_at',
            'Expedido en' => 'expedition_at',
            // 'Fecha de inicio' => 'start_at',
            // 'Fecha de fin' => 'end_at',
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
