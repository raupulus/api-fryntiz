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
 * @property string|null $title Título del hobby
 * @property string|null $description Descripción del hobby
 * @property string|null $url URL del hobby
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Curriculum $curriculum
 * @property-read string $url_image
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumHobby whereUrl($value)
 *
 * @mixin \Eloquent
 */
class CurriculumHobby extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Aficción';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Aficciones';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_hobbies';

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_hobbies';

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
        'description',
        'url',
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
            'Título' => 'title',
            'Descripción' => 'description',
            'URL' => 'url',
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
            'description' => [
                'type' => 'text',
            ],
            'url' => [
                'type' => 'link',
            ],
        ];
    }
}
