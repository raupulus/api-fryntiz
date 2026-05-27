<?php

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices;

use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\CreateHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\EditHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\ListHardwareDevices;
use App\Models\Hardware\HardwareDevice;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HardwareDeviceResource extends Resource
{
    protected static ?string $model = HardwareDevice::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Hardware';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Dispositivo';

    protected static ?string $pluralModelLabel = 'Dispositivos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Imagen principal')
                    ->schema([
                        FileUpload::make('image_id')
                            ->image()
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'flex justify-center mx-auto'])
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                Section::make('Información')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Usuario'),
                        Select::make('hardware_type_id')
                            ->relationship('hardwareType', 'name')
                            ->label('Tipo de hardware'),
                        TextInput::make('referred_thing_id')
                            ->numeric()
                            ->label('ID componente asociado'),
                        TextInput::make('name')->label('Nombre'),
                        TextInput::make('name_friendly')->label('Nombre amigable'),
                        TextInput::make('ref')->label('Referencia'),
                        TextInput::make('brand')->label('Marca'),
                        TextInput::make('model')->label('Modelo'),
                        TextInput::make('software_version')->label('Versión de software'),
                        TextInput::make('hardware_version')->label('Versión de hardware'),
                        TextInput::make('serial_number')->label('Número de serie'),
                        TextInput::make('battery_type')->label('Tipo de batería'),
                        TextInput::make('battery_nominal_capacity')
                            ->numeric()->label('Capacidad nominal de batería'),
                        TextInput::make('url_company')->label('Sitio web empresa'),
                        TextInput::make('ip_local')->label('IP Local'),
                        TextInput::make('ip_public')->label('IP Pública'),
                        DateTimePicker::make('buy_at')->label('Comprado el'),
                        DateTimePicker::make('last_seen_at')->label('Última vez en línea'),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('image_id')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('ID Imagen'),
                TextColumn::make('hardwareType.name')
                    ->label('Tipo de hardware')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('referredThing.name') // Assumes related model has name
                    ->label('Componente asociado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name_friendly')
                    ->label('Nombre amigable')
                    ->searchable(),
                TextColumn::make('ref')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable(),
                TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable(),
                TextColumn::make('software_version')
                    ->label('Versión software')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('hardware_version')
                    ->label('Versión hardware')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('serial_number')
                    ->label('Nº Serie')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('battery_type')
                    ->label('Tipo batería')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('battery_nominal_capacity')
                    ->label('Capacidad batería')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url_company')
                    ->label('Sitio web')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('buy_at')
                    ->label('Comprado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_seen_at')
                    ->label('Última vez visto')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ip_local')
                    ->label('IP Local')
                    ->searchable(),
                TextColumn::make('ip_public')
                    ->label('IP Pública')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hardware_type_id')
                    ->relationship('hardwareType', 'name')->label('Tipo'),
            ])
            ->recordActions([
                EditAction::make(),
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
            RelationManagers\ComponentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHardwareDevices::route('/'),
            'create' => CreateHardwareDevice::route('/create'),
            'edit' => EditHardwareDevice::route('/{record}/edit'),
        ];
    }
}
