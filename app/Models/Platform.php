<?php

namespace App\Models;

use App;
use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function route;
use App\Models\Content\Content;

/**
 * Class Platform
 */
class Platform extends BaseAbstractModelWithTableCrud
{
    use ImageTrait;

    protected $table = 'platforms';

    //protected $with = ['image'];
    protected $appends = ['urlImageMicro', 'urlImageSmall'];

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'domain', 'url_about', 'youtube_channel_id',
        'youtube_presentation_video_id', 'twitter', 'twitter_token', 'mastodon', 'mastodon_token', 'twitch', 'tiktok',
        'instagram'
        ];


    public static function  getModuleName(): string
    {
        return 'platform';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Plataforma',
            'plural' => 'Plataformas',
            'add' => 'Agregar plataforma',
            'edit' => 'Editar plataforma',
            'delete' => 'Eliminar plataforma',
        ];
    }

    /**
     * Asocia con el usuario al que pertenece la plataforma.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     *
     * @return HasMany
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'platform_id', 'id')
            ->orderByDesc('is_featured')
            ->orderByDesc('updated_at');
    }

    /**
     * Asocia todos los contenidos creados para la plataforma.
     *
     * @return HasMany
     */
    public function contentsActive(): HasMany
    {
        return $this->contents()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ;
    }

    /**
     * Devuelve el contenido de tipo páginas asociado a la plataforma actual.
     *
     * @return HasMany
     */
    public function contentPages(): HasMany
    {
        return $this->contentsActive()->where('type_id', 1);
    }

    /**
     * Asocia todos los tags para la plataforma.
     *
     * @return BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'platform_tags', 'platform_id', 'tag_id');
    }

    /**
     * Asocia todas las categorías para la plataforma.
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'platform_categories', 'platform_id', 'category_id');
    }

    /**
     * Asocia a la imagen principal.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

    /**
     * Devuelve todos los dominios asignados a las plataformas.
     *
     * @return array
     */
    public static function getAllDomains(): array
    {
        return self::whereNotNull('domain')
            ->whereNotIn('domain', ['', ' ', false])
            ->pluck('domain')
            ->toArray();
    }


    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return App\Policies\PlatformPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
     *
     * @return array
     */
    public static function getFieldsValidation(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,{id}',
            'description' => 'nullable|string|max:255',
        ];
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads(): array
    {
        return [
            'id' => 'ID',
            'image_id' => 'Imagen ID',
            'urlImage' => 'Imagen',
            'title' => 'Título',
            'slug' => 'Slug',
            'domain' => 'Dominio',
            'description' => 'Descripción',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    public static function getTableCellsInfo():array
    {
        return [
            'id' => [
                'type' => 'integer',
            ],
            'image_id' => [
                'type' => 'hidden',
            ],
            'urlImage' => [
                'type' => 'image',
            ],
            'title' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'slug' => [
                'type' => 'text',
            ],
            'domain' => [
                'type' => 'text',
            ],
            'description' => [
                'type' => 'text',
            ],

        ];
    }

    /**
     * Devuelve las rutas de acciones
     *
     */
    public static function getTableActionsInfo():Collection
    {
        // TODO Crear policies para devolver solo acciones permitidas ahora.

        return collect([
            [
                'type' => 'update',
                'name' => 'Editar',
                'url' => route(self::getCrudRoutes()['edit'], '[id]'),
                'method' => 'GET',
                /*
                'params' => [

                ]
                */
            ],
            [
                'type' => 'delete',
                'name' => 'Eliminar',
                'url' => route(self::getCrudRoutes()['destroy']),
                'method' => 'DELETE',
                'ajax' => true
            ]
        ]);
    }
}
