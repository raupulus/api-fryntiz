<?php

namespace App\Filament\Admin\Resources\CV\Curricula\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExperienceSelfEmployedRelationManager extends RelationManager
{
    protected static string $relationship = 'experienceSelfEmployed';

    protected static ?string $title = 'Experiencia autónomo';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(511)->required()->label('Título'),
            TextInput::make('company')->maxLength(511)->label('Empresa'),
            TextInput::make('position')->maxLength(255)->label('Puesto'),
            DateTimePicker::make('start_at')->label('Inicio'),
            DateTimePicker::make('end_at')->label('Fin'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
            Textarea::make('note')->rows(2)->columnSpanFull()->label('Notas'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título'),
                TextColumn::make('start_at')->dateTime('d/m/Y')->label('Inicio'),
                TextColumn::make('end_at')->dateTime('d/m/Y')->label('Fin'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
