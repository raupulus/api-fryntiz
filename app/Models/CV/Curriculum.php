<?php

declare(strict_types=1);

namespace App\Models\CV;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\BaseModels\BaseModel;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Collection<int, CurriculumAcademicComplementary> $academicComplementary
 * @property-read int|null $academic_complementary_count
 * @property-read Collection<int, CurriculumAcademicComplementaryOnline> $academicComplementaryOnline
 * @property-read int|null $academic_complementary_online_count
 * @property-read Collection<int, CurriculumAcademicTraining> $academicTraining
 * @property-read int|null $academic_training_count
 * @property-read Collection<int, CurriculumCollaboration> $collaborations
 * @property-read int|null $collaborations_count
 * @property-read Collection<int, CurriculumExperienceAccredited> $experienceAccredited
 * @property-read int|null $experience_accredited_count
 * @property-read Collection<int, CurriculumExperienceAdditional> $experienceAdditional
 * @property-read int|null $experience_additional_count
 * @property-read Collection<int, CurriculumExperienceNoAccredited> $experienceNoAccredited
 * @property-read int|null $experience_no_accredited_count
 * @property-read Collection<int, CurriculumExperienceOther> $experienceOther
 * @property-read int|null $experience_other_count
 * @property-read Collection<int, CurriculumExperienceSelfEmployed> $experienceSelfEmployed
 * @property-read int|null $experience_self_employed_count
 * @property-read string $url_image
 * @property-read mixed $url_image_thumbnail_large
 * @property-read mixed $url_image_thumbnail_medium
 * @property-read mixed $url_image_thumbnail_micro
 * @property-read mixed $url_image_thumbnail_normal
 * @property-read mixed $url_image_thumbnail_small
 * @property-read Collection<int, CurriculumHobby> $hobbies
 * @property-read int|null $hobbies_count
 * @property-read File|null $image
 * @property-read Collection<int, CurriculumJob> $jobs
 * @property-read int|null $jobs_count
 * @property-read Collection<int, CurriculumProject> $projects
 * @property-read int|null $projects_count
 * @property-read Collection<int, CurriculumRepository> $repositories
 * @property-read int|null $repositories_count
 * @property-read Collection<int, CurriculumService> $services
 * @property-read int|null $services_count
 * @property-read Collection<int, CurriculumSkill> $skills
 * @property-read int|null $skills_count
 * @property-read User|null $user
 *
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
 *
 * @mixin \Eloquent
 */
class Curriculum extends BaseModel
{
    use SoftDeletes;

    protected $table = 'cv';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    protected $fillable = [
        'user_id',
        'image_id',
        'title',
        'presentation',
        'is_active',
        'is_downloadable',
        'is_default',
        'is_public',
        'slug',
        'visibility',
        'share_token',
        'pdf_path',
        'pdf_needs_regeneration',
        'pdf_generated_at',
    ];

    /**
     * Usuario propietario del curriculum.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected $casts = [
        'visibility' => CurriculumVisibilityEnum::class,
        'is_active' => 'boolean',
        'is_downloadable' => 'boolean',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'pdf_needs_regeneration' => 'boolean',
        'pdf_generated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Curriculum $cv) {
            // Sólo un CV por usuario puede ser el predeterminado.
            if ($cv->is_default) {
                self::where('user_id', $cv->user_id)
                    ->where('id', '!=', $cv->id ?? 0)
                    ->update(['is_default' => false]);
            }

            // Un CV compartido sin token no lo ve nadie: se genera solo.
            if ($cv->visibility === CurriculumVisibilityEnum::Shared && blank($cv->share_token)) {
                $cv->share_token = self::newShareToken();
            }

            // `is_public` se queda sincronizado mientras exista la columna, para
            // que los formularios y consultas antiguos no se contradigan con la
            // visibilidad real.
            $cv->is_public = $cv->visibility === CurriculumVisibilityEnum::Public;
        });

        // Cualquier cambio en el CV invalida el PDF (B5).
        static::updated(function (Curriculum $cv) {
            if ($cv->wasChanged('pdf_path') || $cv->wasChanged('pdf_needs_regeneration') || $cv->wasChanged('pdf_generated_at')) {
                return;
            }

            $cv->markPdfForRegeneration();
        });
    }

    /**
     * Token de compartición: 64 caracteres hexadecimales, imposibles de adivinar.
     */
    public static function newShareToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Marca el PDF como caducado sin disparar más eventos.
     *
     * Lo llaman también las secciones del CV: si cambias un trabajo, el PDF que
     * hay guardado ya no vale.
     */
    public function markPdfForRegeneration(): void
    {
        if ($this->pdf_needs_regeneration) {
            return;
        }

        static::withoutEvents(function () {
            $this->newQuery()
                ->whereKey($this->getKey())
                ->update(['pdf_needs_regeneration' => true]);
        });

        $this->pdf_needs_regeneration = true;
    }

    /**
     * ¿Puede verlo alguien que llega sin autenticarse, con este token?
     */
    public function isVisibleTo(?string $shareToken = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->visibility) {
            CurriculumVisibilityEnum::Public => true,
            CurriculumVisibilityEnum::Shared => $shareToken !== null
                && $this->share_token !== null
                && hash_equals($this->share_token, $shareToken),
            CurriculumVisibilityEnum::Private => false,
        };
    }

    /**
     * Sólo los currículums que salen en el listado público (B3).
     *
     * @param  Builder<Curriculum>  $query
     * @return Builder<Curriculum>
     */
    public function scopePublicOnly(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('visibility', CurriculumVisibilityEnum::Public->value);
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
     */
    public function safeDelete(): bool
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
