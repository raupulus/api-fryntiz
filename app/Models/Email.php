<?php

namespace App\Models;

use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Policies\EmailPolicy;
use Illuminate\Support\Collection;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Email
 */
class Email extends BaseAbstractModelWithTableCrud
{
    protected $table = 'emails';

    protected $fillable = [
        'user_id',
        'language_id',
        'email',
        'attributes',
        'subject',
        'message',
        'privacity',
        'contactme',
        'server_ip',
        'client_ip',
        'app_name',
        'app_domain',
        'client_user_agent',
        'client_referer',
        'client_accept_language',
    ];

    public static function  getModuleName(): string
    {
        return 'email';
    }

    public static function getModelTitles(): array
    {
        return [
            'singular' => 'Email',
            'plural' => 'Emails',
            'add' => 'Agregar email',
            'edit' => 'Editar email',
            'delete' => 'Eliminar email',
        ];
    }

    /**
     * Relación con el usuario.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con el idioma del usuario
     *
     * @return BelongsTo
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id', 'id');
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     *
     * @return string|null
     */
    protected static function getPolicy(): string|null
    {
        return EmailPolicy::class;
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
            'app_name' => 'required|string|max:255',
            'captcha_score' => 'required|numeric|min:0|max:10',
            'email' => 'required|string|max:511|mail',
            'subject' => 'required|string|max:511',
            'message' => 'required|string|max:1024',
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
            'app_name' => 'app_name',
            'captcha_score' => 'captcha_score',
            'email' => 'email',
            'subject' => 'Subject',
            'message' => 'message',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return string[][]
     */
    public static function getTableCellsInfo():array
    {
        return [
            'id' => [
                'type' => 'integer',
            ],
            'app_name' => [
                'type' => 'text',
            ],
            'captcha_score' => [
                'type' => 'text',
            ],
            'email' => [
                'type' => 'text',
            ],
            'subject' => [
                'type' => 'text',
            ],
            'message' => [
                'type' => 'text',
            ],

        ];
    }

    /**
     * Devuelve las rutas de acciones
     *
     * @return Collection
     */
    public static function getTableActionsInfo(): Collection
    {
        return collect([]);
    }
}


