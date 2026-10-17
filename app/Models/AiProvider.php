<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EncryptedString;
use App\Enums\AiProviderEnum;
use App\Services\Ai\AiImageProvider;
use App\Services\Ai\AiTextProvider;
use Database\Factories\AiProviderFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Un proveedor de IA del asistente del editor de páginas: su modelo y su
 * clave, para todas las plataformas. Sólo lo gestiona el superadmin
 * (`AiProviderPolicy`).
 *
 * La clave va cifrada y nunca sale en `toArray()`. Si la `APP_KEY` ha cambiado
 * desde que se guardó, `api_key` se lee como `null` y el proveedor deja de
 * estar disponible hasta que se vuelve a pegar (ver `EncryptedString`).
 *
 * @property int $id
 * @property AiProviderEnum $provider
 * @property string $model
 * @property string|null $image_model Modelo de imágenes; vacío, no genera imágenes
 * @property string|null $api_key
 * @property bool $is_active
 * @property bool $is_default
 * @property bool $sends_reasoning_level
 * @property bool $requests_reasoning_summary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiProvider extends Model
{
    /** @use HasFactory<AiProviderFactory> */
    use HasFactory;

    public const KEY_SET = 'set';

    public const KEY_MISSING = 'missing';

    public const KEY_UNREADABLE = 'unreadable';

    protected $table = 'ai_providers';

    protected $fillable = [
        'provider',
        'model',
        'image_model',
        'api_key',
        'is_active',
        'is_default',
        'sends_reasoning_level',
        'requests_reasoning_summary',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'provider' => AiProviderEnum::class,
            'api_key' => EncryptedString::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sends_reasoning_level' => 'boolean',
            'requests_reasoning_summary' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Sólo uno sale elegido en el asistente.
        static::saved(function (AiProvider $provider): void {
            if ($provider->is_default && ($provider->wasRecentlyCreated || $provider->wasChanged('is_default'))) {
                static::query()->whereKeyNot($provider->getKey())->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Los que puede usar el asistente: activos y con una clave que se puede
     * leer. El predeterminado, primero.
     *
     * @return Collection<int, static>
     */
    public static function usable(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->whereNotNull('api_key')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->filter(fn (AiProvider $provider): bool => $provider->isUsable())
            ->values();
    }

    public function isUsable(): bool
    {
        return $this->is_active && $this->keyStatus() === self::KEY_SET;
    }

    /**
     * Los que pueden generar imágenes: usables y con modelo de imágenes. El
     * predeterminado, primero.
     *
     * @return Collection<int, static>
     */
    public static function usableForImages(): Collection
    {
        return static::usable()
            ->filter(fn (AiProvider $provider): bool => $provider->supportsImages())
            ->values();
    }

    public function supportsImages(): bool
    {
        return $this->isUsable() && filled(trim((string) $this->image_model));
    }

    /**
     * Si la clave está puesta, falta o no se puede leer.
     */
    public function keyStatus(): string
    {
        if (blank($this->attributes['api_key'] ?? null)) {
            return self::KEY_MISSING;
        }

        return filled($this->api_key) ? self::KEY_SET : self::KEY_UNREADABLE;
    }

    /**
     * «Gemini · gemini-3.8-flash».
     */
    public function label(): string
    {
        return $this->provider->label().' · '.$this->model;
    }

    public function connector(): AiTextProvider
    {
        return app($this->provider->connector());
    }

    /**
     * «Gemini · gemini-nano-banana-2.1».
     */
    public function imageLabel(): string
    {
        return $this->provider->label().' · '.$this->image_model;
    }

    public function imageConnector(): AiImageProvider
    {
        return app($this->provider->imageConnector());
    }
}
