<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'components';

    protected static ?string $title = 'Componentes instalados';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('hardware_available_component_id')
                ->relationship('availableComponent', 'name')
                ->searchable()->preload()->required()->label('Componente'),
            TextInput::make('serial_number')->maxLength(255)->label('Nº serie'),
            TextInput::make('notes')->maxLength(511)->label('Notas'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('availableComponent.name')->label('Componente'),
                TextColumn::make('serial_number')->label('Nº serie'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Instalado'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Componente')
                    ->modelLabel('Componente'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
