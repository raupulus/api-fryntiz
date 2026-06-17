<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoutesRelationManager extends RelationManager
{
    protected static string $relationship = 'routes';

    protected static ?string $title = 'Rutas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('flight')->maxLength(255)->label('Vuelo'),
            TextInput::make('lat')->numeric()->label('Latitud'),
            TextInput::make('lon')->numeric()->label('Longitud'),
            TextInput::make('altitude')->numeric()->label('Altitud'),
            TextInput::make('speed')->numeric()->label('Velocidad'),
            TextInput::make('track')->numeric()->label('Track'),
            TextInput::make('squawk')->maxLength(10)->label('Squawk'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flight')->label('Vuelo'),
                TextColumn::make('lat')->label('Lat'),
                TextColumn::make('lon')->label('Lon'),
                TextColumn::make('altitude')->label('Alt'),
                TextColumn::make('speed')->label('Vel'),
                TextColumn::make('seen_at')->dateTime('d/m/Y H:i')->sortable()->label('Visto'),
            ])
            ->defaultSort('seen_at', 'desc')
            ->headerActions([CreateAction::make()])
            ->recordActions([DeleteAction::make()]);
    }
}
