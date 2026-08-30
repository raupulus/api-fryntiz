<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Concerns\HasImageFileUpload;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    use HasImageFileUpload;

    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * El campo de imagen no se rellena con el id guardado: el componente espera
     * un fichero, no una clave foránea. Si se dejara, Filament intentaría
     * pintar el número como si fuera una ruta.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['image_id']);

        return $data;
    }

    /**
     * Convierte la imagen subida en un registro de `files` y guarda su id (N232).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'contents');
    }
}
