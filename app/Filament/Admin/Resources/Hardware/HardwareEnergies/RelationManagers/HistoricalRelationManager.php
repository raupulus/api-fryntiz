<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * RelationManager para las series históricas y sesiones de odómetro de un elemento.
 */
class HistoricalRelationManager extends RelationManager
{
    protected static string $relationship = 'historical';

    protected static ?string $title = 'Histórico y sesiones';

    protected static ?string $modelLabel = 'sesión histórica';

    protected static ?string $pluralModelLabel = 'sesiones históricas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('session_index')->numeric()->minValue(1)->required()->label('Número de sesión (odómetro)'),
            TextInput::make('days_operating')->numeric()->minValue(0)->label('Días de operación'),
            TextInput::make('readings_count')->numeric()->minValue(0)->label('Conteo de lecturas'),
            TextInput::make('energy_wh')->numeric()->step(0.0001)->suffix(' Wh')->label('Energía acumulada (Wh)'),
            TextInput::make('energy_ah')->numeric()->step(0.0001)->suffix(' Ah')->label('Carga acumulada (Ah)'),
            TextInput::make('number_battery_full_charges')->numeric()->minValue(0)->label('Ciclos de carga completa'),
            TextInput::make('number_battery_over_discharges')->numeric()->minValue(0)->label('Ciclos de sobredescarga'),
            TextInput::make('voltage_min')->numeric()->step(0.001)->suffix(' V')->label('Tensión mínima histórica'),
            TextInput::make('voltage_max')->numeric()->step(0.001)->suffix(' V')->label('Tensión máxima histórica'),
            TextInput::make('amperage_min')->numeric()->step(0.001)->suffix(' A')->label('Corriente mínima histórica'),
            TextInput::make('amperage_max')->numeric()->step(0.001)->suffix(' A')->label('Corriente máxima histórica'),
            TextInput::make('power_min')->numeric()->step(0.001)->suffix(' W')->label('Potencia mínima histórica'),
            TextInput::make('power_max')->numeric()->step(0.001)->suffix(' W')->label('Potencia máxima histórica'),
            TextInput::make('temperature_min')->numeric()->step(0.1)->suffix(' °C')->label('Temp. mínima histórica'),
            TextInput::make('temperature_max')->numeric()->step(0.1)->suffix(' °C')->label('Temp. máxima histórica'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_index')
                    ->badge()
                    ->color('primary')
                    ->label('Sesión')
                    ->sortable(),
                TextColumn::make('energy_wh')
                    ->suffix(' Wh')
                    ->sortable()
                    ->label('Energía total (Wh)'),
                TextColumn::make('energy_ah')
                    ->suffix(' Ah')
                    ->sortable()
                    ->label('Carga total (Ah)'),
                TextColumn::make('days_operating')
                    ->numeric()
                    ->sortable()
                    ->label('Días operando'),
                TextColumn::make('readings_count')
                    ->numeric()
                    ->sortable()
                    ->label('Lecturas'),
                TextColumn::make('number_battery_full_charges')
                    ->numeric()
                    ->label('Cargas completas')
                    ->toggleable(),
                TextColumn::make('number_battery_over_discharges')
                    ->numeric()
                    ->label('Sobredescargas')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Última actualización'),
            ])
            ->defaultSort('session_index', 'desc')
            ->headerActions([
                CreateAction::make()->label('Nueva sesión'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
