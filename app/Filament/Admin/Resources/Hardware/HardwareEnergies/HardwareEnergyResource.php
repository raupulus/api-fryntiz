<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies;

use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\CreateHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\EditHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\ListHardwareEnergies;
use App\Models\Hardware\HardwareEnergy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HardwareEnergyResource extends Resource
{
    protected static ?string $model = HardwareEnergy::class;

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBattery100;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Configuración energía';

    protected static ?string $pluralModelLabel = 'Energía';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('hardware_device_id')
                    ->relationship('hardware', 'name')
                    ->required()->searchable()->preload()
                    ->label('Dispositivo monitor'),
                Select::make('hardware_device_monitorized_id')
                    ->relationship('monitorized', 'name')
                    ->required()->searchable()->preload()
                    ->label('Dispositivo monitorizado'),
                Toggle::make('is_generator')->label('Es generador'),
                TextInput::make('sensor_position')
                    ->numeric()->label('Posición del sensor'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hardware.name')
                    ->label('Dispositivo monitor')
                    ->sortable(),
                TextColumn::make('monitorized.name')
                    ->label('Dispositivo monitorizado')
                    ->sortable(),
                IconColumn::make('is_generator')
                    ->label('Generador')
                    ->boolean(),
                TextColumn::make('sensor_position')
                    ->label('Posición sensor')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            RelationManagers\PowerLoadsRelationManager::class,
            RelationManagers\PowerGeneratorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareEnergies::route('/'),
            'create' => CreateHardwareEnergy::route('/create'),
            'edit' => EditHardwareEnergy::route('/{record}/edit'),
        ];
    }
}
