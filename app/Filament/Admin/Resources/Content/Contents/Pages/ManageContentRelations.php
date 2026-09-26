<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\ContributorsRelationManager;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\GalleriesRelationManager;
use App\Filament\Admin\Resources\Content\Contents\RelationManagers\RelatedRelationManager;
use BackedEnum;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * «Relacionados»: galerías, colaboradores y contenidos relacionados, con la
 * autorización de cada uno (F5).
 */
class ManageContentRelations extends EditRecord
{
    use ContentSectionPage;

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'Relacionados';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected function getHeaderActions(): array
    {
        return [$this->previewAction()];
    }

    /**
     * @return array<class-string>
     */
    protected function getAllRelationManagers(): array
    {
        return [
            GalleriesRelationManager::class,
            ContributorsRelationManager::class,
            RelatedRelationManager::class,
        ];
    }

    /**
     * Sin formulario: sólo las tres relaciones.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getRelationManagersContentComponent()]);
    }
}
