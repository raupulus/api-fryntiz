<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Concerns\HasImageFileUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * «Datos»: la primera sección de la ficha (título, slug, plataforma, autor,
 * estado, tipo, extracto, imagen, vídeo y enlaces).
 */
class EditContent extends EditRecord
{
    use ContentSectionPage;
    use HasImageFileUpload;

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'Datos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static bool $formActionsAreSticky = true;

    protected function getHeaderActions(): array
    {
        return [
            $this->saveOnTopAction(),
            $this->previewAction(),
            DeleteAction::make()->label('Eliminar'),
            RestoreAction::make()->label('Restaurar'),
            ForceDeleteAction::make()->label('Eliminar definitivamente'),
        ];
    }

    /**
     * Convierte la imagen subida en un registro de `files` y guarda su id (N232).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'contents', webpOriginal: true);
    }
}
