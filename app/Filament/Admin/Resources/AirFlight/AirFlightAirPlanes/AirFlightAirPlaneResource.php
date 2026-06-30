<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes;

use App\Filament\Admin\Clusters\AirFlight;
use App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\Pages\CreateAirFlightAirPlane;
use App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\Pages\EditAirFlightAirPlane;
use App\Filament\Admin\Resources\AirFlight\AirFlightAirPlanes\Pages\ListAirFlightAirPlanes;
use App\Models\AirFlight\AirFlightAirPlane;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AirFlightAirPlaneResource extends Resource
{
    protected static ?string $model = AirFlightAirPlane::class;

    protected static ?string $cluster = AirFlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Avión';

    protected static ?string $pluralModelLabel = 'Aviones';

    protected static ?string $recordTitleAttribute = 'icao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')->columns(2)->schema([
                    TextInput::make('icao')->required()->maxLength(10)
                        ->unique(ignoreRecord: true)->rule('alpha_num')
                        ->label('Código ICAO (6 hex)'),
                    Select::make('category')->options([
                        'A1' => 'A1 — Ligero', 'A2' => 'A2 — Pequeño', 'A3' => 'A3 — Mediano',
                        'A4' => 'A4 — Pesado', 'A5' => 'A5 — Muy pesado', 'A6' => 'A6 — High vortex',
                        'A7' => 'A7 — Rotor', 'B0' => 'B0 — Sin clasificar',
                        'B1' => 'B1 — Glider', 'B2' => 'B2 — Más ligero que el aire',
                    ])->nullable()->label('Categoría'),
                    TextInput::make('country')->maxLength(255)->label('País'),
                    TextInput::make('flag')->url()->maxLength(255)->label('URL bandera'),
                    Select::make('user_id')
                        ->relationship('user', 'name')->searchable()->preload()->label('Usuario'),
                    Select::make('hardware_device_id')
                        ->relationship('hardwareDevice', 'name_friendly')->searchable()->preload()->label('Receptor'),
                ]),
                Section::make('Detección')->columns(3)->schema([
                    DateTimePicker::make('seen_first_at')->label('Primera detección'),
                    DateTimePicker::make('seen_last_at')->label('Última detección'),
                    DateTimePicker::make('route_last_at')->label('Última ruta'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('flag')->label('🇽')->square(),
                TextColumn::make('icao')->searchable()->sortable()->copyable()->label('ICAO'),
                TextColumn::make('country')->toggleable()->label('País'),
                TextColumn::make('category')->badge()->label('Cat.'),
                TextColumn::make('routes_count')->counts('routes')->label('Rutas'),
                TextColumn::make('seen_last_at')->dateTime('d/m/Y H:i')->sortable()->label('Visto'),
                TextColumn::make('hardwareDevice.name_friendly')->badge()->toggleable()->label('Receptor'),
            ])
            ->filters([
                SelectFilter::make('category')->options([
                    'A1' => 'A1', 'A2' => 'A2', 'A3' => 'A3', 'A4' => 'A4', 'A5' => 'A5', 'A6' => 'A6', 'A7' => 'A7', 'B1' => 'B1', 'B2' => 'B2',
                ])->label('Categoría'),
                Filter::make('recent')
                    ->query(fn ($q) => $q->where('seen_last_at', '>=', now()->subHour()))
                    ->label('Última hora'),
                Filter::make('with_routes')
                    ->query(fn ($q) => $q->whereHas('routes'))->label('Con rutas'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
            RelationManagers\RoutesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAirFlightAirPlanes::route('/'),
            'create' => CreateAirFlightAirPlane::route('/create'),
            'edit' => EditAirFlightAirPlane::route('/{record}/edit'),
        ];
    }
}
