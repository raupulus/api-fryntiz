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
 * @property string $title Título de la experiencia
 * @property string|null $position Puesto ocupado en la experiencia
 * @property string|null $company Empresa donde trabajó
 * @property string|null $description Descripción
 * @property string|null $note Notas
 * @property string|null $start_at Fecha de inicio
 * @property string|null $end_at Fecha de fin
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Curriculum $curriculum
 * @property-read string $url_image
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumExperienceAccredited whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CurriculumExperienceAccredited extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Experiencia Acreditada';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Experiencias Acreditadas';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_experience_accredited';

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_experience_accredited';

    /**
     * @var string[] Campos que admiten asignación masiva.
     *
     * Va en cada modelo y no en CurriculumBaseSection: las secciones
     * comparten clase padre pero no esquema, así que una lista única en la
     * abstracta descartaría en silencio las columnas propias de cada una.
     */
    protected $fillable = [
        'curriculum_id',
        'image_id',
        'title',
        'position',
        'company',
        'description',
        'note',
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
