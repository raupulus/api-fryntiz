<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * RelationManager para las lecturas granulares de telemetría de un elemento de energía.
 */
class ReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'readings';

    protected static ?string $title = 'Lecturas de telemetría';

    protected static ?string $modelLabel = 'lectura';

    protected static ?string $pluralModelLabel = 'lecturas de telemetría';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('created_at')->label('Fecha / Hora')->disabled(),
            TextInput::make('voltage')->numeric()->step(0.001)->suffix(' V')->label('Tensión'),
            TextInput::make('amperage')->numeric()->step(0.001)->suffix(' A')->label('Corriente'),
            TextInput::make('power')->numeric()->step(0.001)->suffix(' W')->label('Potencia'),
            TextInput::make('delta_seconds')->numeric()->minValue(0)->suffix(' s')->label('Duración intervalo'),
            TextInput::make('energy_wh')->numeric()->step(0.0001)->suffix(' Wh')->label('Energía (Wh)'),
            TextInput::make('energy_ah')->numeric()->step(0.0001)->suffix(' Ah')->label('Carga (Ah)'),
            TextInput::make('energy_source')->maxLength(16)->label('Origen energía (device/derived)'),
            TextInput::make('voltage_source')->maxLength(16)->label('Origen tensión (measured/nominal)'),
            TextInput::make('battery_voltage')->numeric()->step(0.01)->suffix(' V')->label('Tensión batería'),
            TextInput::make('battery_percentage')->numeric()->minValue(0)->maxValue(100)->suffix(' %')->label('Nivel batería %'),
            TextInput::make('temperature')->numeric()->step(0.1)->suffix(' °C')->label('Temperatura'),
            TextInput::make('fan')->numeric()->minValue(0)->label('Ventilador'),
            TextInput::make('charging_status')->numeric()->label('Código estado carga'),
            TextInput::make('charging_status_label')->maxLength(255)->label('Etiqueta estado carga'),
            Toggle::make('light_status')->label('Luz activa'),
            TextInput::make('light_brightness')->numeric()->minValue(0)->maxValue(100)->label('Brillo luz %'),
            Toggle::make('is_suspicious')->label('Marcar como sospechosa'),
            TextInput::make('suspicious_reason')->maxLength(255)->label('Motivo sospecha'),
        ])->columns(2);
    }

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
            ->headerActions([
                CreateAction::make()->label('Nueva lectura'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
