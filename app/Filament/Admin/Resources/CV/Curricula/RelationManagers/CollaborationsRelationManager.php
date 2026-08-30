<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curricula\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollaborationsRelationManager extends RelationManager
{
    protected static string $relationship = 'collaborations';

    protected static ?string $title = 'Colaboraciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(255)->label('Title'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripcion'),
            TextInput::make('url')->url()->maxLength(511)->label('URL'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Title'),
                TextColumn::make('url')->label('Url'),
            ])
            // El orden de un CV es información, no un detalle: se arrastra a
            // mano y se guarda en `position` (B4).
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
