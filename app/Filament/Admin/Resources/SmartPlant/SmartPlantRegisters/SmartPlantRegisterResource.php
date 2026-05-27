<?php

namespace App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters;

use App\Filament\Admin\Clusters\SmartPlant;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages\CreateSmartPlantRegister;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages\EditSmartPlantRegister;
use App\Filament\Admin\Resources\SmartPlant\SmartPlantRegisters\Pages\ListSmartPlantRegisters;
use App\Models\SmartPlant\SmartPlantRegister;
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

class SmartPlantRegisterResource extends Resource
{
    protected static ?string $model = SmartPlantRegister::class;

    protected static ?string $cluster = SmartPlant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Lectura SmartPlant';

    protected static ?string $pluralModelLabel = 'Lecturas SmartPlant';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('plant_id')
                    ->relationship('plant', 'name')
                    ->label('Planta')
                    ->required(),
                Select::make('hardware_device_id')
                    ->relationship('hardwareDevice', 'name')
                    ->label('Dispositivo Hardware'),
                TextInput::make('uv')
                    ->label('UV')
                    ->numeric(),
                TextInput::make('temperature')
                    ->label('Temperatura')
                    ->numeric(),
                TextInput::make('pressure')
                    ->label('Presión')
                    ->numeric(),
                TextInput::make('humidity')
                    ->label('Humedad')
                    ->numeric(),
                TextInput::make('soil_humidity')
                    ->label('Humedad suelo')
                    ->required()
                    ->numeric(),
                TextInput::make('soil_humidity_raw')
                    ->label('Humedad suelo bruto')
                    ->numeric(),
                Toggle::make('full_water_tank')
                    ->label('Depósito Lleno')
                    ->required(),
                Toggle::make('waterpump_enabled')
                    ->label('Bomba de agua')
                    ->required(),
                Toggle::make('vaporizer_enabled')
                    ->label('Vaporizador')
                    ->required(),
            ]);
    }

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmartPlantRegisters::route('/'),
            'create' => CreateSmartPlantRegister::route('/create'),
            'edit' => EditSmartPlantRegister::route('/{record}/edit'),
        ];
    }
}
