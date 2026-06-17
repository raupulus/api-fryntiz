<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseAbstractModelWithTableCrud;
use App\Policies\EmailPolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Email
 *
 * @property int $id
 * @property int|null $user_id Usuario al que se le envía el mensaje
 * @property int|null $language_id Idioma del usuario que envía el mensaje para que se le responda en su idioma
 * @property string $email
 * @property string $subject
 * @property string $message
 * @property bool $privacity Indica si acepta políticas de privacidad desde el apartado que envía el mensaje de contacto.
 * @property bool $contactme Indica si permite ser contactado.
 * @property numeric|null $captcha_score Puntuación asignada por un validador de captcha (del 1.0 al 10.0)
 * @property string|null $server_ip Ip del servidor desde el que se ha enviado.
 * @property string|null $client_ip Ip que se ha obtenido del cliente.
 * @property string|null $client_user_agent Navegador que se ha obtenido del navegador cliente.
 * @property string|null $client_referer Página desde la que se ha obtenido que viene el navegador cliente.
 * @property array<array-key, mixed>|null $client_accept_language Listado de idiomas que acepta el navegador cliente.
 * @property string|null $app_name Nombre de la aplicación desde la que se envía el mensaje.
 * @property string|null $app_domain Dominio de la aplicación desde la que se envía el mensaje.
 * @property array<array-key, mixed>|null $attributes Otros datos de interés dentro de un json, por ejemplo: {phone: XXX-XXX-XXX, age: 29}
 * @property int $priority Prioridad del mensaje, cuanto más alto más prioridad. Menor a 3 no se envía.
 * @property bool $send Indica si el mensaje se debe enviar o no.
 * @property int $attempts Número de intentos de envío de este mensaje.
 * @property Carbon|null $sent_at Fecha y hora en la que se envió el mensaje.
 * @property int|null $error_code Código de error en caso de que el mensaje no se haya enviado correctamente.
 * @property Carbon|null $error_at Fecha y hora en la que se produjo el error.
 * @property string|null $error_message Mensaje de error en caso de que el mensaje no se haya enviado correctamente.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read Language|null $language
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereAppDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereAppName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereAttributes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereCaptchaScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereClientAcceptLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereClientIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereClientReferer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereClientUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereContactme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereErrorAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email wherePrivacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereSend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereServerIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Email whereUserId($value)
 *
 * @mixin \Eloquent
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
        'captcha_score',
        'priority',
        'send',
        'attempts',
        'sent_at',
        'error_code',
        'error_at',
        'error_message',
    ];

    protected $casts = [
        'attributes' => 'array',
        'client_accept_language' => 'array',
        'privacity' => 'boolean',
        'contactme' => 'boolean',
        'send' => 'boolean',
        'sent_at' => 'datetime',
        'error_at' => 'datetime',
        'captcha_score' => 'decimal:1',
    ];

    public static function getModuleName(): string
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
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con el idioma del usuario
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id', 'id');
    }

    /****************** Métodos para tablas dinámicas ******************/

    /**
     * Devuelve el modelo de la política asociada.
     */
    protected static function getPolicy(): ?string
    {
        return EmailPolicy::class;
    }

    /**
     * Devuelve un array con el nombre del atributo y la validación aplicada.
     * Esto está pensado para usarlo en el frontend
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
    public static function getTableCellsInfo(): array
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
     */
    public static function getTableActionsInfo(): Collection
    {
        return collect([]);
    }
}
