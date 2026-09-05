<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curriculum\RelationManagers;

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

class AcademicTrainingRelationManager extends RelationManager
{
    protected static string $relationship = 'academicTraining';

    protected static ?string $title = 'Formación reglada';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(511)->required()->label('Título'),
            TextInput::make('entity')->maxLength(511)->label('Entidad'),
            TextInput::make('instructor')->maxLength(511)->label('Instructor'),
            TextInput::make('hours')->numeric()->label('Horas'),
            DateTimePicker::make('start_at')->label('Inicio'),
            DateTimePicker::make('end_at')->label('Fin'),
            DateTimePicker::make('expedition_at')->label('Expedición'),
            TextInput::make('credential_id')->maxLength(511)->label('ID credencial'),
            TextInput::make('credential_url')->url()->maxLength(511)->label('URL credencial'),
            TextInput::make('learned')->maxLength(511)->label('Aprendido'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título'),
                TextColumn::make('entity')->label('Entidad'),
                TextColumn::make('start_at')->dateTime('d/m/Y')->label('Inicio'),
                TextColumn::make('end_at')->dateTime('d/m/Y')->label('Fin'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
