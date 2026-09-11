<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters;

use App\Filament\Admin\Clusters\SmartPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages\ListSmartPlantRegisters;
use App\Models\SmartPlant\SmartPlantRegister;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Sólo lectura: los registros los sube el dispositivo IoT, no se crean ni se
 * editan a mano desde el panel (ver `SmartPlantRegisterPolicy`). Lo único que
 * puede hacer un admin o superadmin aquí es mirar, filtrar y borrar —por
 * ejemplo, limpiar las lecturas de una prueba con un dispositivo.
 */
class SmartPlantRegisterResource extends Resource
{
    protected static ?string $model = SmartPlantRegister::class;

    protected static ?string $cluster = SmartPlant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Lectura SmartPlant';

    protected static ?string $pluralModelLabel = 'Lecturas SmartPlant';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plant.name')
                    ->label('Planta')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hardwareDevice.name')
                    ->label('Dispositivo')
                    ->sortable(),
                TextColumn::make('uv')
                    ->label('UV')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('temperature')
                    ->label('Temp.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pressure')
                    ->label('Presión')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('humidity')
                    ->label('Hum.')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('soil_humidity')
                    ->label('Hum. Suelo')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('soil_humidity_raw')
                    ->label('Hum. Suelo bruto')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('full_water_tank')
                    ->label('Depósito Lleno')
                    ->boolean(),
                IconColumn::make('waterpump_enabled')
                    ->label('Bomba de agua')
                    ->boolean(),
                IconColumn::make('vaporizer_enabled')
                    ->label('Vaporizador')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Para acotar a la planta o al dispositivo de la prueba que
                // se está revisando, no para depurar un valor concreto: eso
                // ya lo hace el orden por columna.
                SelectFilter::make('plant_id')
                    ->relationship('plant', 'name')
                    ->label('Planta'),
                SelectFilter::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name')
                    ->label('Dispositivo'),
            ])
            ->recordActions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmartPlantRegisters::route('/'),
        ];
    }
}
