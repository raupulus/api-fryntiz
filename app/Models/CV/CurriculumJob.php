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
 * @property string $title Título de la colaboración
 * @property string|null $description Descripción del proyecto
 * @property string|null $url Url principal hacia el sitio oficial
 * @property string|null $urlinfo Url de información sobre el proyecto
 * @property string|null $repository Url del repositorio
 * @property string|null $role Rol en el proyecto
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Curriculum $curriculum
 * @property-read string $url_image
 * @property-read File|null $image
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereRepository($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumJob whereUrlinfo($value)
 *
 * @mixin \Eloquent
 */
class CurriculumJob extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Trabajo';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Trabajos';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_jobs';

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_jobs';

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
        'urlinfo',
        'repository',
        'role',
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
            'URL Info' => 'urlinfo',
            'Repositorio' => 'repository',
            'Role' => 'role',
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
            'urlinfo' => [
                'type' => 'link',
            ],
            'repository' => [
                'type' => 'link',
            ],
            'role' => [
                'type' => 'badge',
            ],
        ];
    }
}
