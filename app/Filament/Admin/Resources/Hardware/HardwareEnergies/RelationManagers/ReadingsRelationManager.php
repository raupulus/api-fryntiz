<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Las lecturas crudas de un elemento de energía. **Sólo lectura.**
 *
 * No se pueden crear ni editar desde aquí: la telemetría entra exclusivamente
 * por `POST /api/v2/energy/readings`. Editar a mano un vatio es inventarse un
 * dato, y deja de haber forma de saber qué número salió de un aparato. Si hay
 * que rectificar, se arregla el firmware —o el código, si el fallo es nuestro—.
 *
 * Borrar sí, para poder limpiar una serie corrupta, pero sólo administradores y
 * con confirmación: el aparato ya mandó ese dato y no lo reenvía.
 */
class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    protected static ?string $title = 'Lecturas de telemetría';

    protected static ?string $modelLabel = 'lectura';

    protected static ?string $pluralModelLabel = 'lecturas de telemetría';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->label('Fecha / Hora'),
                TextColumn::make('voltage')
                    ->suffix(' V')
                    ->label('Tensión')
                    ->placeholder('—'),
                TextColumn::make('amperage')
                    ->suffix(' A')
                    ->label('Corriente')
                    ->placeholder('—'),
                TextColumn::make('power')
                    ->suffix(' W')
                    ->sortable()
                    ->label('Potencia')
                    ->placeholder('—'),
                TextColumn::make('delta_seconds')
                    ->suffix(' s')
                    ->label('Duración')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('energy_wh')
                    ->suffix(' Wh')
                    ->label('Δ Wh')
                    ->toggleable(),
                TextColumn::make('energy_ah')
                    ->suffix(' Ah')
                    ->label('Δ Ah')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('battery_percentage')
                    ->suffix(' %')
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state < 30 => 'danger',
                        $state < 60 => 'warning',
                        default => 'success',
                    })
                    ->label('Batería')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('battery_voltage')
                    ->suffix(' V')
                    ->label('V Batería')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('temperature')
                    ->suffix(' °C')
                    ->label('Temp.')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fan')
                    ->badge()
                    ->label('Ventilador')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('charging_status_label')
                    ->badge()
                    ->label('Estado')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('energy_source')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'device' ? 'info' : 'gray')
                    ->label('Origen')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_suspicious')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->label('Sospechosa')
                    ->toggleable(),
                TextColumn::make('suspicious_reason')
                    ->label('Motivo sospecha')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->filters([
                TernaryFilter::make('is_suspicious')
                    ->label('Sospechosa')
                    ->placeholder('Todas las lecturas')
                    ->trueLabel('Sólo sospechosas')
                    ->falseLabel('Sólo válidas'),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(static fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Borrar esta lectura')
                    ->modalDescription(
                        'Esto no se puede deshacer y el dato no se puede volver a pedir: '
                        .'el aparato ya lo mandó y no lo reenvía.'
                    ),
            ]);
    }
}
