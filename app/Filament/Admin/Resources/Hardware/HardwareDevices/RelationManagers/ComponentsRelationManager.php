<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                ->searchable()->preload()->required()->label('Tipo de componente'),
            TextInput::make('name')->maxLength(255)->label('Nombre'),
            TextInput::make('brand')->maxLength(255)->label('Marca'),
            TextInput::make('model')->maxLength(255)->label('Modelo'),
            TextInput::make('quantity')->maxLength(255)->label('Cantidad'),
            TextInput::make('power')->maxLength(255)->label('Potencia (W)'),
            DateTimePicker::make('buy_at')->label('Fecha de compra'),
            Textarea::make('description')->columnSpanFull()->label('Descripción'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('availableComponent.name')->label('Tipo')->searchable(),
                TextColumn::make('name')->label('Nombre')->searchable()->placeholder('—'),
                TextColumn::make('brand')->label('Marca')->toggleable()->placeholder('—'),
                TextColumn::make('model')->label('Modelo')->toggleable()->placeholder('—'),
                TextColumn::make('quantity')->label('Cantidad')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('buy_at')->date('d/m/Y')->label('Comprado')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('d/m/Y')->label('Instalado')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Componente')
                    ->modelLabel('Componente'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
