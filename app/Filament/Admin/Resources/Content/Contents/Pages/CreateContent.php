<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Concerns\HasImageFileUpload;
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
        return $this->resolveImageUpload($data, 'image_id', 'contents');
    }
}
