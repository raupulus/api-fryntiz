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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereCredentialId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereCredentialUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereEntity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereExpeditionAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereExpires($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereInstructor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereLearned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumAcademicComplementary whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_academic_complementary';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Va en cada modelo y no en CurriculumBaseSection: las secciones
     * comparten clase padre pero no esquema, así que una lista única en la
     * abstracta descartaría en silencio las columnas propias de cada una.
     */
    protected $fillable = [
        'curriculum_id',
        'image_id',
        'title',
        'entity',
        'credential_id',
        'credential_url',
        'learned',
        'description',
        'note',
        'hours',
        'instructor',
        'expires',
        'expires_at',
        'expedition_at',
        'start_at',
        'end_at',
    ];

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
