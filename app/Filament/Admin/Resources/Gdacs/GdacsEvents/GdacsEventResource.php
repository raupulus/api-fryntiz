<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Gdacs\GdacsEvents;

use App\Enums\GdacsAlertLevelEnum;
use App\Enums\GdacsEventTypeEnum;
use App\Filament\Admin\Resources\Gdacs\GdacsEvents\Pages\ListGdacsEvents;
use App\Models\Gdacs\GdacsEvent;
use App\Policies\GdacsEventPolicy;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Recurso de solo lectura. `gdacs:sync` es el único que escribe aquí — ver
 * {@see GdacsEventPolicy}, que deniega create/update/delete a
 * todo el mundo, administrador incluido.
 */
class GdacsEventResource extends Resource
{
    protected static ?string $model = GdacsEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    // Justo debajo de AEMET (80).
    protected static ?int $navigationSort = 81;

    protected static ?string $navigationLabel = 'GDACS';

    protected static ?string $modelLabel = 'Evento GDACS';

    protected static ?string $pluralModelLabel = 'Eventos GDACS';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')
                ->columns(2)
                ->schema([
                    TextInput::make('event_type')
                        ->label('Tipo de desastre')
                        ->formatStateUsing(fn (?string $state): string => $state !== null
                            ? (GdacsEventTypeEnum::tryFrom($state)?->label() ?? $state)
                            : ''),
                    TextInput::make('alert_level')
                        ->label('Nivel de alerta')
                        ->formatStateUsing(fn (?string $state): string => $state !== null
                            ? (GdacsAlertLevelEnum::tryFrom($state)?->label() ?? $state)
                            : ''),
                    TextInput::make('name')->label('Título')->columnSpanFull(),
                    TextInput::make('is_current')
                        ->label('¿Sigue activo?')
                        ->formatStateUsing(fn (?bool $state): string => $state ? 'Sí' : 'No'),
                    TextInput::make('distance_km')
                        ->label('Distancia')
                        ->formatStateUsing(fn (?float $state): string => $state !== null ? number_format($state, 1).' km' : ''),
                ])->columnSpanFull(),

            Section::make('Fechas')
                ->columns(3)
                ->schema([
                    TextInput::make('from_date')->label('Desde'),
                    TextInput::make('to_date')->label('Hasta'),
                    TextInput::make('last_modified_at')->label('Última actualización (GDACS)'),
                ])->columnSpanFull(),

            Section::make('Ubicación')
                ->columns(2)
                ->schema([
                    TextInput::make('lat')->label('Latitud'),
                    TextInput::make('lon')->label('Longitud'),
                ])->columnSpanFull(),

            Section::make('Severidad')
                ->columns(2)
                ->schema([
                    TextInput::make('severity_text')->label('Descripción')->columnSpanFull(),
                    TextInput::make('severity_value')->label('Valor'),
                    TextInput::make('severity_unit')->label('Unidad'),
                    TextInput::make('affected_population')
                        ->label('Población afectada')
                        ->formatStateUsing(fn (?int $state): string => $state !== null ? (string) $state : 'Sin dato'),
                ])->columnSpanFull(),

            Section::make('Más información')
                ->schema([
                    TextInput::make('report_url')->label('Informe de GDACS')->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('from_date', 'desc')
            ->columns([
                TextColumn::make('alert_level')
                    ->label('Alerta')
                    ->badge()
                    ->formatStateUsing(fn (GdacsAlertLevelEnum $state): string => $state->label())
                    ->color(fn (GdacsAlertLevelEnum $state): string => $state->color()),
                TextColumn::make('event_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (GdacsEventTypeEnum $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Título')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('distance_km')
                    ->label('Distancia')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' km')
                    ->sortable(),
                IconColumn::make('is_current')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('from_date')
                    ->label('Desde')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('to_date')
                    ->label('Hasta')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('severity_text')
                    ->label('Severidad')
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('last_modified_at')
                    ->label('Actualizado (GDACS)')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('alert_level')
                    ->label('Nivel de alerta')
                    ->options(array_combine(
                        array_map(fn (GdacsAlertLevelEnum $c) => $c->value, GdacsAlertLevelEnum::cases()),
                        array_map(fn (GdacsAlertLevelEnum $c) => $c->label(), GdacsAlertLevelEnum::cases()),
                    )),
                SelectFilter::make('event_type')
                    ->label('Tipo de desastre')
                    ->options(array_combine(
                        array_map(fn (GdacsEventTypeEnum $c) => $c->value, GdacsEventTypeEnum::cases()),
                        array_map(fn (GdacsEventTypeEnum $c) => $c->label(), GdacsEventTypeEnum::cases()),
                    )),
                TernaryFilter::make('is_current')->label('Activo'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGdacsEvents::route('/'),
        ];
    }
}
