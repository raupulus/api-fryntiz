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
 * @property string $name Nombre del skill
 * @property int|null $level Nivel de conocimiento del 1 al 10
 * @property string|null $description Descripción del skill
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Curriculum $curriculum
 * @property-read string $url_image
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumSkill whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CurriculumSkill extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Habilidad';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Habilidades';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_skills';

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_skills';

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
        'name',
        'level',
        'description',
        'position',
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
            'Nombre' => 'name',
            'Nivel' => 'level',
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
            'level' => [
                'type' => 'text',
            ],
            'description' => [
                'type' => 'text',
            ],
        ];
    }
}
