<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $platform_id
 * @property string $email
 * @property string|null $name
 * @property bool $is_verified
 * @property string|null $verification_token
 * @property Carbon|null $verified_at
 * @property string $unsubscribe_token
 * @property string $status
 * @property Carbon|null $unsubscribed_at
 * @property string|null $subscription_source
 * @property string $language
 * @property array<array-key, mixed>|null $preferences
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read bool $can_receive_emails
 * @property-read string $status_label
 * @property-read Platform $platform
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter byEmail(string $email)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter byLanguage(string $language)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter byPlatform($platformId)
 * @method static \Database\Factories\NewsletterFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter subscribed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter verified()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter wherePlatformId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter wherePreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereSubscriptionSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereUnsubscribeToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereUnsubscribedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereVerificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Newsletter whereVerifiedAt($value)
 *
 * @mixin \Eloquent
 */
class Newsletter extends BaseModel
{
    use HasFactory;

    protected $table = 'newsletter';

    protected $fillable = [
        'platform_id',
        'email',
        'name',
        'is_verified',
        'verification_token',
        'verified_at',
        'unsubscribe_token',
        'status',
        'unsubscribed_at',
        'subscription_source',
        'language',
        'preferences',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'preferences' => 'array',
        'metadata' => 'array',
    ];

    // Estados disponibles
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_UNSUBSCRIBED = 'unsubscribed';

    const STATUS_BOUNCED = 'bounced';

    // Fuentes de suscripción
    const SOURCE_WEB = 'web';

    const SOURCE_API = 'api';

    const SOURCE_IMPORT = 'import';

    const SOURCE_ADMIN = 'admin';

    /**
     * Relación con la plataforma
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * Boot method para generar tokens automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($newsletter) {
            if (empty($newsletter->verification_token)) {
                $newsletter->verification_token = $newsletter->generateVerificationToken();
            }
            if (empty($newsletter->unsubscribe_token)) {
                $newsletter->unsubscribe_token = $newsletter->generateUnsubscribeToken();
            }
        });
    }

    /**
     * Generar token de verificación único
     */
    public function generateVerificationToken(): string
    {
        do {
            $token = Str::random(60);
        } while (static::where('verification_token', $token)->exists());

        return $token;
    }

    /**
     * Generar token de desuscripción único
     */
    public function generateUnsubscribeToken(): string
    {
        do {
            $token = Str::random(60);
        } while (static::where('unsubscribe_token', $token)->exists());

        return $token;
    }

    /**
     * Verificar email del suscriptor
     */
    public function verifyEmail(): bool
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verification_token' => null,
            'status' => self::STATUS_ACTIVE,
        ]);

        return true;
    }

    /**
     * Regenerar token de verificación
     */
    public function regenerateVerificationToken(): string
    {
        $token = $this->generateVerificationToken();
        $this->update([
            'verification_token' => $token,
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return $token;
    }

    /**
     * Desuscribir al usuario
     */
    public function unsubscribe(): bool
    {
        $this->update([
            'status' => self::STATUS_UNSUBSCRIBED,
            'unsubscribed_at' => now(),
        ]);

        return true;
    }

    /**
     * Reactivar suscripción
     */
    public function reactivate(): bool
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'unsubscribed_at' => null,
        ]);

        return true;
    }

    /**
     * Cambiar estado de la suscripción
     */
    public function changeStatus(string $status): bool
    {
        if (! in_array($status, [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_UNSUBSCRIBED, self::STATUS_BOUNCED])) {
            return false;
        }

        $updateData = ['status' => $status];

        if ($status === self::STATUS_UNSUBSCRIBED) {
            $updateData['unsubscribed_at'] = now();
        } elseif ($status === self::STATUS_ACTIVE) {
            $updateData['unsubscribed_at'] = null;
        }

        $this->update($updateData);

        return true;
    }

    /**
     * Marcar como bounced (rebotado)
     */
    public function markAsBounced(): bool
    {
        return $this->changeStatus(self::STATUS_BOUNCED);
    }

    /**
     * Actualizar preferencias del suscriptor
     */
    public function updatePreferences(array $preferences): bool
    {
        $this->update(['preferences' => $preferences]);

        return true;
    }

    /**
     * Agregar metadata adicional
     */
    public function addMetadata(array $metadata): bool
    {
        $currentMetadata = $this->metadata ?? [];
        $newMetadata = array_merge($currentMetadata, $metadata);

        $this->update(['metadata' => $newMetadata]);

        return true;
    }

    /**
     * Verificar si el email es válido
     */
    public function isValidEmail(): bool
    {
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Verificar si la suscripción está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verificar si está verificado
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * Verificar si está desuscrito
     */
    public function isUnsubscribed(): bool
    {
        return $this->status === self::STATUS_UNSUBSCRIBED;
    }

    /**
     * Verificar si está rebotado
     */
    public function isBounced(): bool
    {
        return $this->status === self::STATUS_BOUNCED;
    }

    /**
     * Enlace que va dentro del correo de verificación.
     *
     * Apunta a una **página web que no muta nada**: la confirmación es un POST
     * desde un botón. Si el enlace confirmara por sí solo, el antivirus del
     * correo lo seguiría al escanear el mensaje y daría por confirmada una
     * suscripción que nadie ha confirmado.
     *
     * Apuntaba a `route('newsletter.verify')`, un nombre de ruta que no existe
     * en ninguna parte: llamar a este método lanzaba una excepción.
     */
    public function getVerificationUrl(): string
    {
        return route('newsletter.manage', ['token' => $this->verification_token]);
    }

    /**
     * Enlace de baja. Misma página, mismo motivo.
     */
    public function getUnsubscribeUrl(): string
    {
        return route('newsletter.manage', ['token' => $this->unsubscribe_token]);
    }

    /**
     * Destino de la baja de un clic (RFC 8058).
     *
     * Es la URL que se pone en la cabecera `List-Unsubscribe` junto a
     * `List-Unsubscribe-Post: List-Unsubscribe=One-Click`. El cliente de correo
     * hace POST aquí; nunca GET.
     */
    public function getOneClickUnsubscribeUrl(): string
    {
        return route('api.v2.newsletter.subscriptions.unsubscribe', [
            'token' => $this->unsubscribe_token,
        ]);
    }

    /**
     * Scope para obtener solo verificados
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope para obtener solo activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para obtener por plataforma
     */
    public function scopeByPlatform($query, $platformId)
    {
        return $query->where('platform_id', $platformId);
    }

    /**
     * Scope para obtener suscritos (activos y verificados)
     */
    public function scopeSubscribed($query)
    {
        return $query->active()->verified();
    }

    /**
     * Scope para buscar por email
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope para obtener por idioma
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Buscar suscriptor por token de verificación
     */
    public static function findByVerificationToken(string $token): ?self
    {
        return static::where('verification_token', $token)->first();
    }

    /**
     * Buscar suscriptor por token de desuscripción
     */
    public static function findByUnsubscribeToken(string $token): ?self
    {
        return static::where('unsubscribe_token', $token)->first();
    }

    /**
     * Crear o actualizar suscriptor
     *
     * @return array Retorna un array con el objeto newsletter y un booleano indicando si es nuevo
     */
    public static function createOrUpdate(array $data): array
    {
        $newsletter = static::where('platform_id', $data['platform_id'])
            ->where('email', $data['email'])
            ->first();

        $isNew = false;

        if ($newsletter) {
            // Si ya existe y está desuscrito, reactivar
            if ($newsletter->isUnsubscribed()) {
                $newsletter->reactivate();
            }

            // Actualizar datos
            $newsletter->update($data);
        } else {
            // Crear nuevo
            $newsletter = static::create($data);
            $isNew = true;
        }

        return [
            'newsletter' => $newsletter,
            'isNew' => $isNew,
        ];
    }

    /**
     * Obtener estadísticas de la newsletter
     */
    public static function getStats($platformId = null)
    {
        $query = static::query();

        if ($platformId) {
            $query->byPlatform($platformId);
        }

        return [
            'total' => $query->count(),
            'active' => $query->active()->count(),
            'verified' => $query->verified()->count(),
            'subscribed' => $query->subscribed()->count(),
            'unsubscribed' => $query->where('status', self::STATUS_UNSUBSCRIBED)->count(),
            'bounced' => $query->where('status', self::STATUS_BOUNCED)->count(),
        ];
    }

    /**
     * Accessor para obtener el estado legible
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_ACTIVE => 'Activo',
            self::STATUS_INACTIVE => 'Inactivo',
            self::STATUS_UNSUBSCRIBED => 'Desuscrito',
            self::STATUS_BOUNCED => 'Rebotado',
        ];

        return $labels[$this->status] ?? 'Desconocido';
    }

    /**
     * Accessor para determinar si puede recibir emails
     */
    public function getCanReceiveEmailsAttribute(): bool
    {
        return $this->isActive() && $this->isVerified();
    }
}
