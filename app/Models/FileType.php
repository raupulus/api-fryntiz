<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Traits\BelongsToUser;
use Illuminate\Support\Carbon;

/**
 * App\Models\FileType
 *
 * @property int $id
 * @property int|null $user_id Usuario asociado
 * @property string $type Tipo de archivo
 * @property string $mime Tipo mime que representa el tipo de archivo
 * @property string $extension Extensión con la que se representa de forma mayoritaria.
 * @property string|null $icon16
 * @property string|null $icon32
 * @property string|null $icon64
 * @property string|null $icon128
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $url_image
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereIcon128($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereIcon16($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereIcon32($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereIcon64($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileType whereUserId($value)
 *
 * @mixin \Eloquent
 */
class FileType extends BaseModel
{
    use BelongsToUser;

    protected $table = 'file_types';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    protected $fillable = [
        'user_id',
        'type',
        'mime',
        'extension',
        // Los cuatro iconos SÍ son asignables: `FileTypeResource` los ofrece
        // como campos de subida del formulario. El `$guarded` anterior los
        // excluía, así que Filament los descartaba en silencio y el icono que
        // se subía desde el panel no se guardaba nunca. Nada más los escribe:
        // sólo se llenan desde ese formulario.
        'icon16',
        'icon32',
        'icon64',
        'icon128',
    ];

    /**
     * Devuelve la url hacia el icono principal del tipo de archivo.
     */
    public function getUrlImageAttribute(): string
    {
        if (! $this->icon128) {
            return asset('images/icons/file_128x128.webp');
        }

        return asset('storage/'.$this->icon128);
    }

    /**
     * Añade un nuevo tipo de archivo a la base de datos.
     *
     * @param  string  $mime  Tipo mime del archivo, por ejemplo: image/png
     * @param  string  $extension  Extensión del archivo, por ejemplo: png
     * @param  string|null  $type  Tipo de archivo, por ejemplo: image
     */
    public static function addFileType(string $mime, string $extension, ?string $type = null): ?FileType
    {
        if (! $mime || (count(explode('/', $mime)) !== 2)) {
            return null;
        }

        if (! $extension) {
            $extension = explode('/', $mime)[1];
        }

        if (! $type) {
            $type = explode('/', $mime)[0];
        }

        if (! $extension || ! $type) {
            return null;
        }

        return self::firstOrCreate([
            'mime' => $mime,
        ], [
            'user_id' => auth()->id(),
            'mime' => $mime,
            'extension' => $extension,
            'type' => $type,
        ]);
    }
}
