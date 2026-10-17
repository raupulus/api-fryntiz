<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Content\Content;
use Database\Factories\PlatformEditorProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un perfil de editor de una plataforma para el asistente IA: cómo se escribe
 * un tipo de entrada (una guía, una noticia, un blog…).
 *
 * Se elige a mano en cada entrada (`contents.editor_profile_id`), y su
 * instrucción sustituye a la de la plataforma. Sólo vale en su plataforma: si
 * la entrada cambia de plataforma, se queda sin perfil.
 *
 * @property int $id
 * @property int $platform_id
 * @property string $name
 * @property string $system_instruction
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlatformEditorProfile extends Model
{
    /** @use HasFactory<PlatformEditorProfileFactory> */
    use HasFactory;

    protected $table = 'platform_editor_profiles';

    protected $fillable = ['platform_id', 'name', 'system_instruction'];

    /**
     * @return BelongsTo<Platform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }

    /**
     * @return HasMany<Content, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'editor_profile_id');
    }
}
