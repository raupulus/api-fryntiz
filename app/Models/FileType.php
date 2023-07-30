<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FileType
 */
class FileType extends Model
{
    protected $table = 'file_types';
    protected $guarded = [
        'id',
        'icon128',
        'icon64',
        'icon32',
        'icon16',
    ];

    /**
     * Devuelve la url para actualizar el icono del tipo de archivo.
     *
     * @return string
     */
    public function getUrlIconUpdateAttribute(): string
    {
        return route('dashboard.app.file_types.update.icon', $this->id);
    }

    public function getUrlImageAttribute(): string
    {
        if (!$this->icon128) {
            return asset('images/icons/file_128x128.webp');
        }

        return asset('storage/' . $this->icon128);
    }


    /**
     * Añade un nuevo tipo de archivo a la base de datos.
     *
     * @param string $mime Tipo mime del archivo, por ejemplo: image/png
     * @param string $extension Extensión del archivo, por ejemplo: png
     * @param string|null $type Tipo de archivo, por ejemplo: image
     * @return FileType|null
     */
    public static function addFileType(string $mime, string $extension, string $type = null): ?FileType
    {
        if (!$mime || (count(explode('/', $mime)) !== 2)) {
            return null;
        }

        if (!$extension) {
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
        ],[
            'user_id' => auth()->id(),
            'mime' => $mime,
            'extension' => $extension,
            'type' => $type,
        ]);
    }
}


