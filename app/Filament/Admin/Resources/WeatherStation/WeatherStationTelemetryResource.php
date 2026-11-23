<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WeatherStation;

use App\Filament\Admin\Resources\WeatherStation\Pages\ListWeatherStationTelemetry;
use App\Models\WeatherStation\MeteorologyResumeToday;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Supervisión de telemetría física de estaciones meteorológicas
 * (`meteorology_resume_today`). Las lecturas las suben los sensores por la
 * API V2; desde aquí se inspeccionan o se limpian anomalías.
 */
class WeatherStationTelemetryResource extends Resource
{
    protected static ?string $model = MeteorologyResumeToday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 82;

    protected static ?string $navigationLabel = 'Estación meteorológica';

    protected static ?string $modelLabel = 'Lectura meteorológica';

    protected static ?string $pluralModelLabel = 'Lecturas meteorológicas';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $slug = 'weather-station';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Condiciones atmosféricas')
                ->columns(3)
                ->schema([
                    TextEntry::make('hardwareDevice.name')->label('Estación')->placeholder('—'),
                    TextEntry::make('temperature')->label('Temperatura')->suffix(' °C')->placeholder('—'),
                    TextEntry::make('humidity')->label('Humedad')->suffix(' %')->placeholder('—'),
                    TextEntry::make('pressure')->label('Presión')->suffix(' hPa')->placeholder('—'),
                    TextEntry::make('rain')->label('Lluvia acumulada')->suffix(' mm')->placeholder('—'),
                    TextEntry::make('rain_intensity')->label('Intensidad lluvia')->suffix(' mm/h')->placeholder('—'),
                ])->columnSpanFull(),
            Section::make('Viento, luz y calidad del aire')
                ->columns(3)
                ->schema([
                    TextEntry::make('wind_speed')->label('Viento')->suffix(' km/h')->placeholder('—'),
                    TextEntry::make('wind_speed_max')->label('Racha máxima')->suffix(' km/h')->placeholder('—'),
                    TextEntry::make('wind_direction')->label('Dirección')->placeholder('—'),
                    TextEntry::make('light')->label('Luz')->placeholder('—'),
                    TextEntry::make('uv_index')->label('Índice UV')->placeholder('—'),
                    TextEntry::make('air_quality')->label('Calidad del aire')->suffix(' %')->placeholder('—'),
                    TextEntry::make('eco2')->label('eCO₂')->suffix(' ppm')->placeholder('—'),
                    TextEntry::make('tvoc')->label('TVOC')->suffix(' ppb')->placeholder('—'),
                    TextEntry::make('lightning')->label('Rayos')->placeholder('—'),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('hardwareDevice'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('hardwareDevice.name')
                    ->label('Estación')
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('temperature')
                    ->label('Temp.')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' °C')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('humidity')
                    ->label('Hum.')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' %')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('pressure')
                    ->label('Presión')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' hPa')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('wind_speed')
                    ->label('Viento')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' km/h')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('rain')
                    ->label('Lluvia')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' mm')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uv_index')
                    ->label('UV')
                    ->numeric(decimalPlaces: 1)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('air_quality')
                    ->label('Calidad aire')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' %')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('eco2')
                    ->label('eCO₂')
                    ->numeric()
                    ->suffix(' ppm')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tvoc')
                    ->label('TVOC')
                    ->numeric()
                    ->suffix(' ppb')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lightning')
                    ->label('Rayos')
                    ->numeric()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Registrado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name')
                    ->label('Estación'),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeatherStationTelemetry::route('/'),
        ];
    }
}
