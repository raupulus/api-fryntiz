<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curriculum\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SkillsRelationManager extends RelationManager
{
    protected static string $relationship = 'skills';

    protected static ?string $title = 'Habilidades';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->maxLength(255)->required()->label('Nombre'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre'),
            ])
            // El orden de un CV es información, no un detalle: se arrastra a
            // mano y se guarda en `position` (B4).
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
