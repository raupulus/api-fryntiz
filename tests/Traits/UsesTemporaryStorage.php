<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Support\Facades\Storage;

/**
 * Almacenamiento en un directorio temporal durante el test.
 *
 * `Storage::fake()` no vale para las subidas: `File::addFile()` guarda con el
 * disco `local`, pero después procesa la imagen y la sirve con
 * `storage_path()`, así que un disco falso dejaría el fichero en un sitio y el
 * modelo lo buscaría en otro. Aquí se mueven las dos cosas a la vez, la ruta de
 * `storage_path()` y la raíz de los discos, a un directorio temporal que se
 * borra al terminar.
 *
 * Sin esto, cada pasada de `EditorJsTest` dejaba sus imágenes en
 * `storage/app/public/content-pages` de la máquina de desarrollo: 182 ficheros
 * el 2026-09-24.
 */
trait UsesTemporaryStorage
{
    private ?string $temporaryStoragePath = null;

    protected function useTemporaryStorage(): string
    {
        $this->temporaryStoragePath = sys_get_temp_dir().'/api-raupulus-tests-'.bin2hex(random_bytes(6));

        mkdir($this->temporaryStoragePath.'/app/private', 0755, true);
        mkdir($this->temporaryStoragePath.'/app/public', 0755, true);

        $this->app->useStoragePath($this->temporaryStoragePath);

        config([
            'filesystems.disks.local.root' => $this->temporaryStoragePath.'/app',
            'filesystems.disks.public.root' => $this->temporaryStoragePath.'/app/public',
        ]);

        Storage::forgetDisk(['local', 'public']);

        $this->beforeApplicationDestroyed(function (): void {
            if ($this->temporaryStoragePath !== null && is_dir($this->temporaryStoragePath)) {
                exec('rm -rf '.escapeshellarg($this->temporaryStoragePath));
            }
        });

        return $this->temporaryStoragePath;
    }
}
