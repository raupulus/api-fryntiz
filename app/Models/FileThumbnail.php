<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModels\BaseModel;
use App\Traits\HasGenericImages;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

use function route;
use function storage_path;

/**
 * Class FileThumbnail
 *
 * @property int $id
 * @property int|null $file_id Imagen Asociada
 * @property int|null $file_type_id FK al tipo de archivo
 * @property string|null $module Nombre del módulo para acceder por path
 * @property string|null $path Ruta que tiene la aplicación hacia el archivo, por ejemplo: users/avatar
 * @property string|null $storage_path Ruta hacia el archivo en el storage
 * @property string $name Nombre asignado de forma interna en la aplicación, por ejemplo: fg7s97hg98hjsd8gh0d0.jpg
 * @property string|null $key Almacena la clave del tipo de thumbnail (small, medium...)
 * @property int|null $width Ancho de la imagen
 * @property int|null $height Alto de la imagen
 * @property int $size Tamaño de la imagen
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read string $storage_path_file
 * @property-read string $url
 * @property-read FileType|null $fileType
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereFileTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereStoragePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileThumbnail whereWidth($value)
 *
 * @mixin \Eloquent
 */
class FileThumbnail extends BaseModel
{
    use HasGenericImages;

    protected $table = 'file_thumbnails';

    /**
     * @var list<string> Campos que admiten asignación masiva.
     *
     * Lista explícita en lugar de `$guarded = ['id']`: con guarded, cualquier
     * columna nueva queda abierta a mass assignment el día que se añada, sin
     * que nadie tenga que decidirlo (SEC-08).
     */
    protected $fillable = [
        'file_id',
        'file_type_id',
        'module',
        'path',
        'storage_path',
        'name',
        'key',
        'width',
        'height',
        'size',
    ];

    /**
     * Fichero original del que esta miniatura es una versión reducida.
     *
     * `file_thumbnails` **no tiene** `is_private` ni `user_id`: la privacidad y
     * el dueño viven en `files`. Faltaba esta relación, y por eso
     * `FileThumbnailController` comprobaba dos atributos inexistentes —siempre
     * `null`— y servía las miniaturas de los ficheros privados a cualquiera
     * (**N175**).
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Tipo del fichero de la miniatura.
     *
     * La columna `file_type_id` estaba desde el principio y no la leía nadie.
     * Hace falta para el `mime`, que es lo que va en `og:image:type` al
     * compartir un contenido.
     */
    public function fileType(): BelongsTo
    {
        return $this->belongsTo(FileType::class, 'file_type_id');
    }

    /**
     * Devuelve la ruta hacia la imagen.
     *
     * @return string
     */
    public function getUrlAttribute()
    {

        if ($this->path && $this->name && ! $this->deleted_at) {
            return route('file.thumbnail.get', [
                'module' => $this->module,
                'id' => $this->id,
                'slug' => $this->name,
            ]);
        }

        return self::genericImageUrl('not_found');
    }

    /**
     * Devuelve la ruta hacia la imagen dentro del sistema de archivos.
     *
     * @return string
     */
    public function getStoragePathFileAttribute()
    {
        if ($this->storage_path) {
            return storage_path('app/'.$this->storage_path.'/'.$this->name);
        }

        return '';
    }
}
