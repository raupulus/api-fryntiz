<?php

namespace App\Models;

use App;
use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function route;

/**
 * Class Platform
 */
class Platform extends BaseAbstractModelWithTableCrud
{
    use HasFactory;

    protected $table = 'platforms';

    protected $fillable = ['user_id', 'title', 'slug', 'description'];

    public const MODULE_NAME = 'platform';

    public const MODEL_TITLES = [
        'singular' => 'Plataforma',
        'plural' => 'Plataformas',
        'add' => 'Agregar plataforma',
        'edit' => 'Editar plataforma',
        'delete' => 'Eliminar plataforma',
    ];

    public const CRUD_ROUTES = [
        'index' => 'dashboard.' . self::MODULE_NAME . '.index',
        'create' => 'dashboard.' . self::MODULE_NAME . '.create',
        'store' => 'dashboard.' . self::MODULE_NAME . '.store',
        'edit' => 'dashboard.' . self::MODULE_NAME . '.edit',
        'update' => 'dashboard.' . self::MODULE_NAME . '.update',
        'destroy' => 'dashboard.' . self::MODULE_NAME . '.destroy',
    ];

    public const TABLE_AJAX_ROUTES = [
        'get' => 'dashboard.' . self::MODULE_NAME . '.ajax.table.get',
        'actions' => 'dashboard.' . self::MODULE_NAME . '.ajax.table.actions',
    ];


    /**
     * Asocia con el usuario al que pertenece la plataforma.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Elimina de forma segura la instancia actual.
     *
     * @return bool
     */
    public function safeDelete(): bool
    {
        return (bool) $this->delete();
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
            'title' => 'Título',
            'slug' => 'Slug',
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
            'title' => [
                'type' => 'text',
                'wrapper' => 'span',
                'class' => 'text-weight-bold',
            ],
            'slug' => [
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
                'url' => route(self::CRUD_ROUTES['edit'], '[id]'),
                'method' => 'GET',
                /*
                'params' => [

                ]
                */
            ],
            [
                'type' => 'delete',
                'name' => 'Eliminar',
                'url' => route(self::CRUD_ROUTES['destroy']),
                'method' => 'DELETE',
                'ajax' => true
            ]
        ]);
    }
}
