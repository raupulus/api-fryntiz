<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use App\Models\Hardware\HardwareEnergyToday;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * RelationManager para los resúmenes diarios de energía de un elemento.
 */
class TodayRelationManager extends RelationManager
{
    protected static string $relationship = 'today';

    protected static ?string $title = 'Resúmenes diarios';

    protected static ?string $modelLabel = 'resumen diario';

    protected static ?string $pluralModelLabel = 'resúmenes diarios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->required()->label('Fecha'),
            TextInput::make('readings_count')->numeric()->minValue(0)->label('Nº de lecturas'),
            TextInput::make('energy_wh')->numeric()->step(0.0001)->suffix(' Wh')->label('Energía acumulada (Wh)'),
            TextInput::make('energy_ah')->numeric()->step(0.0001)->suffix(' Ah')->label('Carga acumulada (Ah)'),
            TextInput::make('voltage_min')->numeric()->step(0.001)->suffix(' V')->label('Tensión mínima'),
            TextInput::make('voltage_max')->numeric()->step(0.001)->suffix(' V')->label('Tensión máxima'),
            TextInput::make('amperage_min')->numeric()->step(0.001)->suffix(' A')->label('Corriente mínima'),
            TextInput::make('amperage_max')->numeric()->step(0.001)->suffix(' A')->label('Corriente máxima'),
            TextInput::make('power_min')->numeric()->step(0.001)->suffix(' W')->label('Potencia mínima'),
            TextInput::make('power_max')->numeric()->step(0.001)->suffix(' W')->label('Potencia máxima'),
            TextInput::make('battery_min')->numeric()->step(0.01)->suffix(' V')->label('V batería mín.'),
            TextInput::make('battery_max')->numeric()->step(0.01)->suffix(' V')->label('V batería máx.'),
            TextInput::make('battery_percentage_min')->numeric()->minValue(0)->maxValue(100)->suffix(' %')->label('Batería % mín.'),
            TextInput::make('battery_percentage_max')->numeric()->minValue(0)->maxValue(100)->suffix(' %')->label('Batería % máx.'),
            TextInput::make('temperature_min')->numeric()->step(0.1)->suffix(' °C')->label('Temp. mín.'),
            TextInput::make('temperature_max')->numeric()->step(0.1)->suffix(' °C')->label('Temp. máx.'),
            TextInput::make('fan_min')->numeric()->minValue(0)->label('Ventilador mín.'),
            TextInput::make('fan_max')->numeric()->minValue(0)->label('Ventilador máx.'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Fecha'),
                TextColumn::make('energy_wh')
                    ->suffix(' Wh')
                    ->sortable()
                    ->label('Energía (Wh)'),
                TextColumn::make('energy_ah')
                    ->suffix(' Ah')
                    ->sortable()
                    ->label('Carga (Ah)')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('readings_count')
                    ->numeric()
                    ->sortable()
                    ->label('Lecturas'),
                TextColumn::make('power_max')
                    ->suffix(' W')
                    ->label('Pico potencia')
                    ->placeholder('—'),
                TextColumn::make('voltage_range')
                    ->label('Tensión (mín - máx)')
                    ->state(fn (HardwareEnergyToday $record): string => ($record->voltage_min !== null ? number_format((float) $record->voltage_min, 1) : '—').' - '.($record->voltage_max !== null ? number_format((float) $record->voltage_max, 1).' V' : '—'))
                    ->toggleable(),
                TextColumn::make('amperage_max')
                    ->suffix(' A')
                    ->label('Corriente máx.')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('battery_percentage_range')
                    ->label('Batería %')
                    ->state(fn (HardwareEnergyToday $record): string => $record->battery_percentage_min !== null || $record->battery_percentage_max !== null
                        ? ($record->battery_percentage_min ?? '—').'% - '.($record->battery_percentage_max ?? '—').'%'
                        : '—')
                    ->toggleable(),
                TextColumn::make('temperature_max')
                    ->suffix(' °C')
                    ->label('Temp. máx.')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([25, 50, 100])
            ->headerActions([
                CreateAction::make()->label('Nuevo resumen'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
