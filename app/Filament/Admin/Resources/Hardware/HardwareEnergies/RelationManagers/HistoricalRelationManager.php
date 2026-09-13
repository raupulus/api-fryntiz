<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Los acumulados de por vida de un elemento, sesión a sesión. **Sólo lectura.**
 *
 * Cada fila es una sesión de odómetro: cuando el aparato se reinicia y sus
 * contadores vuelven a cero se abre la siguiente, y el total del elemento es la
 * suma de todas. No se crea ni se edita a mano: ver
 * {@see ReadingsRelationManager} para el porqué.
 */
class HistoricalRelationManager extends RelationManager
{
    protected static string $relationship = 'historical';

    protected static ?string $title = 'Histórico y sesiones';

    protected static ?string $modelLabel = 'sesión histórica';

    protected static ?string $pluralModelLabel = 'sesiones históricas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_index')
                    ->badge()
                    ->color('primary')
                    ->label('Sesión')
                    ->sortable(),
                TextColumn::make('energy_wh_source')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'device' => 'info',
                        'derived' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'device' => 'Aparato (odómetro)',
                        'derived' => 'Derivado (suma)',
                        default => (string) $state,
                    })
                    ->label('Origen Wh')
                    ->sortable(),
                TextColumn::make('energy_wh')
                    ->suffix(' Wh')
                    ->sortable()
                    ->label('Energía total (Wh)'),
                TextColumn::make('energy_ah_source')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'device' ? 'info' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'device' => 'Aparato (odómetro)',
                        'derived' => 'Derivado (suma)',
                        default => (string) $state,
                    })
                    ->label('Origen Ah')
                    ->sortable(),
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
            ->recordActions([
                DeleteAction::make()
                    ->visible(static fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Borrar esta sesión histórica')
                    ->modalDescription(
                        'Esto no se puede deshacer y el dato no se puede volver a pedir: '
                        .'el aparato ya lo mandó y no lo reenvía.'
                    ),
            ]);
    }
}
