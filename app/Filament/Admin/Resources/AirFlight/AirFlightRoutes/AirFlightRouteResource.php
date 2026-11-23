<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AirFlight\AirFlightRoutes;

use App\Filament\Admin\Clusters\AirFlight;
use App\Filament\Admin\Resources\AirFlight\AirFlightRoutes\Pages\ListAirFlightRoutes;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\AirFlight\AirFlightRoute;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AirFlightRouteResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = AirFlightRoute::class;

    protected static ?string $cluster = AirFlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Ruta de vuelo';

    protected static ?string $pluralModelLabel = 'Rutas de vuelo';

    protected static ?string $recordTitleAttribute = 'flight';

    protected static ?string $slug = 'routes';

    /**
     * Las rutas las sube la API a partir de lo que capta el hardware. El
     * panel sólo las enseña.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()->preload()->label('Usuario'),
                Select::make('airplane_id')
                    ->relationship('airplane', 'icao')
                    ->required()->searchable()->preload()->label('Avión'),
                Select::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name')
                    ->searchable()->preload()->label('Dispositivo'),
                TextInput::make('squawk')->maxLength(255)->label('Squawk'),
                TextInput::make('flight')->maxLength(255)->label('Vuelo'),
                TextInput::make('lat')
                    ->numeric()->minValue(-90)->maxValue(90)->label('Latitud'),
                TextInput::make('lon')
                    ->numeric()->minValue(-180)->maxValue(180)->label('Longitud'),
                TextInput::make('altitude')
                    ->numeric()->label('Altitud'),
                TextInput::make('vert_rate')
                    ->numeric()->label('Tasa vertical'),
                TextInput::make('track')
                    ->integer()->minValue(0)->maxValue(360)->label('Rumbo'),
                TextInput::make('speed')
                    ->numeric()->minValue(0)->label('Velocidad'),
                DateTimePicker::make('seen_at')->label('Visto el'),
                TextInput::make('messages')
                    ->integer()->minValue(0)->label('Mensajes'),
                TextInput::make('rssi')
                    ->numeric()->maxValue(0)->label('RSSI'),
                TextInput::make('emergency')->maxLength(255)->label('Emergencia'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('seen_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('airplane.icao')
                    ->label('Avión')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('flight')
                    ->label('Vuelo')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('hardwareDevice.name')
                    ->label('Dispositivo')
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->sortable(),
                TextColumn::make('squawk')
                    ->label('Squawk')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lat')
                    ->label('Lat.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lon')
                    ->label('Lon.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('altitude')
                    ->label('Altitud')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('vert_rate')
                    ->label('Tasa vert.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('track')
                    ->label('Rumbo')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ViewAction::make(),
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
        ];
    }
}
