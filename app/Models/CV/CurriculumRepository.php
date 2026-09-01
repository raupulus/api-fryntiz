<?php

declare(strict_types=1);

namespace App\Models\CV;

use App\Models\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class CurriculumRepository.
 *
 * @property int $id
 * @property int $curriculum_id Relación con el curriculum
 * @property int|null $image_id Relación con la imagen asociada
 * @property int|null $repository_type_id Relación con el tipo de repositorios
 * @property string $url Dirección al repositorio
 * @property string $title Título para el repositorio
 * @property string|null $description Descripción del repositorio
 * @property string $name Nombre del repositorio
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Curriculum $curriculum
 * @property-read string $url_image
 * @property-read File|null $image
 * @property-read CurriculumAvailableRepositoryType|null $type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereCurriculumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereRepositoryTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CurriculumRepository whereUrl($value)
 *
 * @mixin \Eloquent
 */
class CurriculumRepository extends CurriculumBaseSection
{
    /**
     * @var string Nombre del modelo en singular.
     */
    public static $singular = 'Repositorio';

    /**
     * @var string Nombre del modelo en plural.
     */
    public static $plural = 'Repositorios';

    /**
     * Ruta hacia el directorio dónde se guardarán las imágenes.
     *
     * @var string
     */
    public static $imagePath = 'cv_repository';

    /**
     * @var string Nombre de la tabla usada por el modelo.
     */
    protected $table = 'cv_repositories';

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
        'repository_type_id',
        'url',
        'title',
        'description',
        'name',
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
            'Tipo' => 'type',
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
            'title' => [
                'type' => 'text',
            ],
            'type' => [
                'relation' => true,  // Indica que es una relación
                'relation_field' => 'name',  // Indica el atributo de la relación
                'type' => 'text',
                'wrapper' => '<span class="badge badge-secondary">{{value}}</span>',
            ],
            'url' => [
                'type' => 'link',
            ],
            'description' => [
                'type' => 'text',
            ],
        ];
    }

    /**
     * Asociación con el tipo de repositorio.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CurriculumAvailableRepositoryType::class, 'repository_type_id', 'id');
    }
}
