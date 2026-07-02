<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RelatedRelationManager extends RelationManager
{
    protected static string $relationship = 'contentsRelated';

    protected static ?string $title = 'Contenidos relacionados';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            // La relación es auto-referenciada (Content -> Content) a través de
            // "content_related". Filament adivina el nombre inverso pluralizando
            // el modelo padre ("contents"), pero ese método no existe: la
            // relación inversa real es contentsRelatedMe().
            ->inverseRelationship('contentsRelatedMe')
            ->columns([
                TextColumn::make('title')->searchable()->label('Título'),
                TextColumn::make('platform.title')->badge()->label('Plataforma'),
            ])
            ->headerActions([AttachAction::make()->preloadRecordSelect()])
            ->recordActions([DetachAction::make()]);
    }
}
