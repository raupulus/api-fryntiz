<?php

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantPlants\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistersRelationManager extends RelationManager
{
    protected static string $relationship = 'registers';

    protected static ?string $title = 'Registros de sensores';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('soil_humidity')->numeric()->minValue(0)->maxValue(100)->label('Humedad suelo (%)'),
            TextInput::make('humidity')->numeric()->label('Humedad aire (%)'),
            TextInput::make('temperature')->numeric()->label('Temperatura (°C)'),
            TextInput::make('pressure')->numeric()->label('Presión'),
            TextInput::make('uv')->numeric()->label('UV'),
            TextInput::make('soil_humidity_raw')->numeric()->label('Humedad suelo (raw)'),
            Toggle::make('full_water_tank')->label('Tanque lleno'),
            Toggle::make('waterpump_enabled')->label('Bomba de agua'),
            Toggle::make('vaporizer_enabled')->label('Vaporizador'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('soil_humidity')->label('Hum. suelo')
                    ->suffix(' %')
                    ->color(fn ($state) => $state < 30 ? 'danger' : ($state < 60 ? 'warning' : 'success')),
                TextColumn::make('temperature')->suffix(' °C')->label('Temp.'),
                TextColumn::make('humidity')->suffix(' %')->label('Hum. aire'),
                TextColumn::make('uv')->label('UV')->toggleable(),
                IconColumn::make('full_water_tank')->boolean()->label('Tanque')->toggleable(),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->label('Lectura'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([DeleteAction::make()]);
    }
}
