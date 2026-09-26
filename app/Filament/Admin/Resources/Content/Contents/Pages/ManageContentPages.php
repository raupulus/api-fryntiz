<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\PagesRelationManager;
use BackedEnum;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * «Páginas»: la tabla de páginas, ahora en su propia sección y no al final de
 * la ficha (E2). La pantalla propia para editarlas llega en F8.
 */
class ManageContentPages extends EditRecord
{
    use ContentSectionPage;

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'Páginas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected function getHeaderActions(): array
    {
        return [$this->previewAction()];
    }

    /**
     * @return array<class-string>
     */
    protected function getAllRelationManagers(): array
    {
        return [PagesRelationManager::class];
    }

    /**
     * Sin formulario: sólo la tabla.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getRelationManagersContentComponent()]);
    }
}
