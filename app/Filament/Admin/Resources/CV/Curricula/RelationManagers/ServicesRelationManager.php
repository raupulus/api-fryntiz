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

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Servicios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->maxLength(511)->required()->label('Nombre'),
            TextInput::make('url')->url()->maxLength(511)->label('URL'),
            Textarea::make('description')->rows(3)->columnSpanFull()->label('Descripción'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('url')->limit(40)->label('URL'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
