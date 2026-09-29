<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    use HasImageFileUpload;

    protected static string $resource = ContentResource::class;

    /**
     * Convierte la imagen subida en un registro de `files` y guarda su id.
     *
     * Sin esto, el campo del formulario no llegaba a ninguna columna: pedía
     * `image_path`, que no existe en ninguna tabla del proyecto (N232).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Un Editor es el autor de lo que crea: el campo le sale bloqueado y,
        // por si llegara otra cosa, aquí se fija (F5).
        $user = auth()->user();

        if ($user instanceof User && ! $user->isAdmin()) {
            $data['author_id'] = $user->id;
        }

        return $this->resolveImageUpload($data, 'image_id', 'contents', webpOriginal: true);
    }

    /**
     * Lo siguiente tras crear un contenido es escribirlo: a «Páginas», con
     * la primera página nueva abierta, en vez de a la ficha.
     */
    protected function getRedirectUrl(): string
    {
        return ContentResource::getUrl('pages', ['record' => $this->getRecord()]);
    }
}
