<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use App\Models\Hardware\HardwareEnergyToday;
use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Los resúmenes diarios de un elemento de energía. **Sólo lectura.**
 *
 * Una fila por elemento y día, construida por la ingesta y refrescada por el
 * cierre nocturno. No se crea ni se edita a mano: ver
 * {@see ReadingsRelationManager} para el porqué.
 */
class TodayRelationManager extends RelationManager
{
    protected static string $relationship = 'today';

    protected static ?string $title = 'Resúmenes diarios';

    protected static ?string $modelLabel = 'resumen diario';

    protected static ?string $pluralModelLabel = 'resúmenes diarios';

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
            ->recordActions([
                DeleteAction::make()
                    ->visible(static fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Borrar esta resumen del día')
                    ->modalDescription(
                        'Esto no se puede deshacer y el dato no se puede volver a pedir: '
                        .'el aparato ya lo mandó y no lo reenvía.'
                    ),
            ]);
    }
}
