<?php

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

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    protected static ?string $title = 'Proyectos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(511)->required()->label('Título'),
            TextInput::make('url')->url()->maxLength(511)->label('URL'),
            TextInput::make('urlinfo')->url()->maxLength(511)->label('URL info'),
            TextInput::make('role')->maxLength(255)->label('Rol'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
            Textarea::make('repository')->rows(2)->columnSpanFull()->label('Repositorio'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título'),
                TextColumn::make('url')->limit(40)->label('URL'),
                TextColumn::make('role')->label('Rol'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
