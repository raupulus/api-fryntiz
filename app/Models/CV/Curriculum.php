<?php

namespace App\Models\CV;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use function array_key_exists;

/**
 * Class Curriculum
 *
 * @property int $id
 * @property int $user_id Relación con el usuario
 * @property int|null $image_id Relación con la imagen asociada
 * @property string $title Título para el curriculum
 * @property string|null $presentation Contenido para la presentación del curriculum
 * @property bool|null $is_active Indica si está activo
 * @property bool|null $is_downloadable Indica si permite descargar el curriculum
 * @property bool|null $is_default Indica si es el curriculum por defecto
 * @property bool|null $is_public Indica si su visibilidad es pública
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumAcademicComplementary> $academicComplementary
 * @property-read int|null $academic_complementary_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumAcademicComplementaryOnline> $academicComplementaryOnline
 * @property-read int|null $academic_complementary_online_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumAcademicTraining> $academicTraining
 * @property-read int|null $academic_training_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumCollaboration> $collaborations
 * @property-read int|null $collaborations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumExperienceAccredited> $experienceAccredited
 * @property-read int|null $experience_accredited_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumExperienceAdditional> $experienceAdditional
 * @property-read int|null $experience_additional_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumExperienceNoAccredited> $experienceNoAccredited
 * @property-read int|null $experience_no_accredited_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumExperienceOther> $experienceOther
 * @property-read int|null $experience_other_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumExperienceSelfEmployed> $experienceSelfEmployed
 * @property-read int|null $experience_self_employed_count
 * @property-read string $url_image
 * @property-read mixed $url_image_thumbnail_large
 * @property-read mixed $url_image_thumbnail_medium
 * @property-read mixed $url_image_thumbnail_micro
 * @property-read mixed $url_image_thumbnail_normal
 * @property-read mixed $url_image_thumbnail_small
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumHobby> $hobbies
 * @property-read int|null $hobbies_count
 * @property-read File|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumJob> $jobs
 * @property-read int|null $jobs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumProject> $projects
 * @property-read int|null $projects_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumRepository> $repositories
 * @property-read int|null $repositories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumService> $services
 * @property-read int|null $services_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CV\CurriculumSkill> $skills
 * @property-read int|null $skills_count
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereImageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereIsDownloadable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum wherePresentation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Curriculum whereUserId($value)
 * @mixin \Eloquent
 */
class Curriculum extends Model
{
    protected $table = 'cv';

    protected $guarded = [
        'id',
    ];

    /**
     * Usuario propietario del curriculum.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Al guardar, asegurar que solo un CV por usuario sea is_default.
     */
    protected static function booted(): void
    {
        static::saving(function (Curriculum $cv) {
            if ($cv->is_default) {
                self::where('user_id', $cv->user_id)
                    ->where('id', '!=', $cv->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(CurriculumRepository::class, 'curriculum_id', 'id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(CurriculumService::class, 'curriculum_id', 'id');
    }

    public function collaborations(): HasMany
    {
        return $this->hasMany(CurriculumCollaboration::class, 'curriculum_id', 'id');
    }

    public function hobbies(): HasMany
    {
        return $this->hasMany(CurriculumHobby::class, 'curriculum_id', 'id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(CurriculumJob::class, 'curriculum_id', 'id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(CurriculumProject::class, 'curriculum_id', 'id');
    }

    public function academicTraining(): HasMany
    {
        return $this->hasMany(CurriculumAcademicTraining::class, 'curriculum_id', 'id');
    }

    public function academicComplementary(): HasMany
    {
        return $this->hasMany(CurriculumAcademicComplementary::class, 'curriculum_id', 'id');
    }

    public function academicComplementaryOnline(): HasMany
    {
        return $this->hasMany(CurriculumAcademicComplementaryOnline::class, 'curriculum_id', 'id');
    }

    public function experienceAccredited(): HasMany
    {
        return $this->hasMany(CurriculumExperienceAccredited::class, 'curriculum_id', 'id');
    }

    public function experienceNoAccredited(): HasMany
    {
        return $this->hasMany(CurriculumExperienceNoAccredited::class, 'curriculum_id', 'id');
    }

    public function experienceSelfEmployed(): HasMany
    {
        return $this->hasMany(CurriculumExperienceSelfEmployed::class, 'curriculum_id', 'id');
    }

    public function experienceAdditional(): HasMany
    {
        return $this->hasMany(CurriculumExperienceAdditional::class, 'curriculum_id', 'id');
    }

    public function experienceOther(): HasMany
    {
        return $this->hasMany(CurriculumExperienceOther::class, 'curriculum_id', 'id');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(CurriculumSkill::class, 'curriculum_id', 'id');
    }

    /**
     * Relación con la imagen asociada al curriculum.
     *
     * @return HasOne
     */
    public function image(): HasOne
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }

    /**
     * Devuelve la ruta hacia la foto asociada al curriculum.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        return $this->image ? $this->image->url : File::urlDefaultImage('large');
    }

    // BORRAR desde aquí (refactorizando antes)

    public function getUrlImageThumbnailMicroAttribute()
    {
        return $this->urlThumbnail('micro');
    }

    public function getUrlImageThumbnailSmallAttribute()
    {
        return $this->urlThumbnail('small');
    }

    public function getUrlImageThumbnailMediumAttribute()
    {
        return $this->urlThumbnail('medium');
    }

    public function getUrlImageThumbnailNormalAttribute()
    {
        return $this->urlThumbnail('normal');
    }

    public function getUrlImageThumbnailLargeAttribute()
    {
        return $this->urlThumbnail('large');
    }

    // BORRAR Hasta aquí

    /**
     * Devuelve el thumbnail de la imagen asociada.
     *
     *
     * @return mixed
     */
    public function urlThumbnail($size = 'medium')
    {
        if ($this->image) {
            return $this->image->thumbnail($size);
        }

        return File::urlDefaultImage($size);
    }

    /**
     * Elimina de forma segura un curriculum y los datos asociados.
     *
     * @return bool
     */
    public function safeDelete()
    {
        // # Elimino la imagen asociada al curriculum y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'Título' => 'title',
            'Descargable' => 'is_downloadable',
            'Fecha de creación' => 'created_at',
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
            'title' => [
                'type' => 'image',
            ],
            'is_downloadable' => [
                'type' => 'boolean',
                'wrapper' => 'span',
                'class' => 'switch-on-off',
            ],
            'created_at' => [
                'type' => 'timestamp',
                'format' => 'd/m/Y',
            ],
        ];
    }

    /**
     * Devuelve los resultados para una página.
     *
     * @param  number  $size  Tamaño de cada página
     * @param  number  $page  Página a la que buscar.
     * @return array
     */
    public static function getTableRowsByPage($size, $page, $columns,
        $orderBy, $orderDirection = 'ASC')
    {
        return self::select($columns)
            ->offset(($page * $size) - $size)
            ->limit($size)
            ->orderBy($orderBy, $orderDirection)
            ->get();
    }

    /**
     * Devuelve un array con todos los atributos para un modelo instanciado
     *
     * @return array
     */
    public function getAllAttributes()
    {
        $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        // $columns = \Schema::getColumnListing($this->table);
        // but it's safer to just get the fillable fields

        $attributes = $this->getAttributes();

        foreach ($columns as $column) {
            if (! array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }

        return $attributes;
    }
}
