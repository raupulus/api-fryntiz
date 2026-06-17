<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleriesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleries';

    protected static ?string $title = 'Galerías';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('gallery_id')->numeric()->required()->label('ID Galería'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('gallery_id')->sortable()->label('ID Galería'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Creada'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([DeleteAction::make()]);
    }
}
