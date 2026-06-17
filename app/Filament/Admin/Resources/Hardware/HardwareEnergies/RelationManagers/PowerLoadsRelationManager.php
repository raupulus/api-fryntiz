<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * RelationManager para las cargas de energía asociadas a un config de energía.
 */
class PowerLoadsRelationManager extends RelationManager
{
    protected static string $relationship = 'powerLoads';

    protected static ?string $title = 'Cargas de energía';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('read_at')->required()->label('Cuándo'),
            TextInput::make('fan')->numeric()->suffix(' RPM')->label('Ventilador'),
            TextInput::make('temperature')->numeric()->step(0.1)->suffix(' °C')->label('Temperatura'),
            TextInput::make('voltage')->numeric()->step(0.01)->suffix(' V')->label('Voltaje'),
            TextInput::make('amperage')->numeric()->step(0.01)->suffix(' A')->label('Amperaje'),
            TextInput::make('power')->numeric()->step(0.01)->suffix(' W')->label('Potencia'),
            TextInput::make('battery_voltage')->numeric()->step(0.01)->suffix(' V')->label('V batería'),
            TextInput::make('battery_percentage')->numeric()->minValue(0)->maxValue(100)->suffix(' %')->label('Batería %'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('read_at')->dateTime('d/m H:i')->sortable()->label('Cuándo'),
            TextColumn::make('power')->suffix(' W')->sortable()->label('Potencia'),
            TextColumn::make('voltage')->suffix(' V')->label('Voltaje'),
            TextColumn::make('amperage')->suffix(' A')->label('Amperaje'),
            TextColumn::make('temperature')->suffix(' °C')->toggleable()->label('Temp.'),
            TextColumn::make('battery_percentage')->suffix(' %')
                ->color(fn ($state) => $state < 30 ? 'danger' : ($state < 60 ? 'warning' : 'success'))
                ->label('Batería'),
            TextColumn::make('fan')->suffix(' RPM')->toggleable()->label('Fan'),
        ])
            ->defaultSort('read_at', 'desc')
            ->paginated([25, 50, 100])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
