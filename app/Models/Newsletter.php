<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class Newsletter extends Model
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
    public function platform()
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
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_UNSUBSCRIBED, self::STATUS_BOUNCED])) {
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
     * Obtener URL de verificación
     */
    public function getVerificationUrl(): string
    {
        return route('newsletter.verify', ['token' => $this->verification_token]);
    }

    /**
     * Obtener URL de desuscripción
     */
    public function getUnsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->unsubscribe_token]);
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
     * @param array $data
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
            'isNew' => $isNew
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
