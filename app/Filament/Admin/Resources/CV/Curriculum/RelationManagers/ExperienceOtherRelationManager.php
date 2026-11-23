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

class ExperienceOtherRelationManager extends RelationManager
{
    protected static string $relationship = 'experienceOther';

    protected static ?string $title = 'Otra exp.';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->label('Título'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título')->limit(40)->tooltip(fn (?string $state): ?string => $state),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
