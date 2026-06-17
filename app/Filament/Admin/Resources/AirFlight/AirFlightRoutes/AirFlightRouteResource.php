<?php

namespace App\Filament\Admin\Resources\AirFlight\AirFlightRoutes;

use App\Filament\Admin\Clusters\AirFlight;
use App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\Pages\CreateAirFlightRoute;
use App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\Pages\EditAirFlightRoute;
use App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\Pages\ListAirFlightRoutes;
use App\Models\AirFlight\AirFlightRoute;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AirFlightRouteResource extends Resource
{
    protected static ?string $model = AirFlightRoute::class;

    protected static ?string $cluster = AirFlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Ruta de vuelo';

    protected static ?string $pluralModelLabel = 'Rutas de vuelo';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()->preload()->label('Usuario'),
                Select::make('airplane_id')
                    ->relationship('airplane', 'name_friendly')
                    ->required()->searchable()->preload()->label('Avión'),
                Select::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name')
                    ->searchable()->preload()->label('Dispositivo'),
                TextInput::make('squawk')->label('Squawk'),
                TextInput::make('flight')->label('Vuelo'),
                TextInput::make('lat')
                    ->numeric()->label('Latitud'),
                TextInput::make('lon')
                    ->numeric()->label('Longitud'),
                TextInput::make('altitude')
                    ->numeric()->label('Altitud'),
                TextInput::make('vert_rate')
                    ->numeric()->label('Tasa vertical'),
                TextInput::make('track')
                    ->numeric()->label('Rumbo'),
                TextInput::make('speed')
                    ->numeric()->label('Velocidad'),
                DateTimePicker::make('seen_at')->label('Visto el'),
                TextInput::make('messages')
                    ->numeric()->label('Mensajes'),
                TextInput::make('rssi')
                    ->numeric()->label('RSSI'),
                TextInput::make('emergency')->label('Emergencia'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable(),
                TextColumn::make('airplane.name_friendly')
                    ->label('Avión')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hardwareDevice.name')
                    ->label('Dispositivo')
                    ->sortable(),
                TextColumn::make('squawk')
                    ->label('Squawk')
                    ->searchable(),
                TextColumn::make('flight')
                    ->label('Vuelo')
                    ->searchable(),
                TextColumn::make('lat')
                    ->label('Lat.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lon')
                    ->label('Lon.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('altitude')
                    ->label('Altitud')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('vert_rate')
                    ->label('Tasa vert.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('track')
                    ->label('Rumbo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('speed')
                    ->label('Velocidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('seen_at')
                    ->label('Visto el')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('messages')
                    ->label('Mensajes')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rssi')
                    ->label('RSSI')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('emergency')
                    ->label('Emergencia')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAirFlightRoutes::route('/'),
            'create' => CreateAirFlightRoute::route('/create'),
            'edit' => EditAirFlightRoute::route('/{record}/edit'),
        ];
    }
}
