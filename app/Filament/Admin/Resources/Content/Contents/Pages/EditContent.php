<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\SavesFromTheHeader;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\Content\Content;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
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
    use SavesFromTheHeader;

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

    /**
     * Si ha cambiado de plataforma, qué ha pasado con sus categorías,
     * etiquetas y relacionados (`Content::moveTaxonomiesToItsPlatform()`).
     */
    protected function afterSave(): void
    {
        /** @var Content $record */
        $record = $this->getRecord();
        $move = $record->taxonomyMove;

        if ($move === null) {
            return;
        }

        $lines = [sprintf('Han pasado a la nueva plataforma %d %s y %d %s.',
            $move['categories'], $move['categories'] === 1 ? 'categoría' : 'categorías',
            $move['tags'], $move['tags'] === 1 ? 'etiqueta' : 'etiquetas',
        )];

        if ($move['left'] !== []) {
            $lines[] = 'No existen en esta plataforma y dejan de verse: '.implode(', ', $move['left']).'. Si las quieres, añádelas a la plataforma y vuelve a elegirlas en «Categorías y etiquetas».';
        }

        if ($move['related'] > 0) {
            $lines[] = sprintf('%d %s de la plataforma anterior dejan de mostrarse: sólo se enlazan contenidos de la misma plataforma.',
                $move['related'], $move['related'] === 1 ? 'contenido relacionado' : 'contenidos relacionados');
        }

        Notification::make()
            ->title('Plataforma cambiada')
            ->body(implode(' ', $lines))
            ->color($move['left'] === [] && $move['related'] === 0 ? 'success' : 'warning')
            ->persistent()
            ->send();
    }
}
