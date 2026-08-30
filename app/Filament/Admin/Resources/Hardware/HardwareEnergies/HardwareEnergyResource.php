<?php

declare(strict_types=1);

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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * El **elemento energético**: un panel, un router, una batería (D81).
 *
 * Esta pantalla es donde se le pone a cada elemento su instalación, su papel y
 * —lo más importante— su **tensión nominal**: es lo que hace que los vatios
 * salgan bien. Sin ella se multiplica la corriente del canal por el único
 * voltaje que traiga la petición, y un panel de 24 V y una Pico de 3,7 V en la
 * misma petición dan números sin sentido.
 */
class HardwareEnergyResource extends Resource
{
    protected static ?string $model = HardwareEnergy::class;

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBattery100;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Elemento energético';

    protected static ?string $pluralModelLabel = 'Elementos de energía';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Qué es y quién lo mide')
                    ->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->label('Nombre')
                            ->placeholder('Panel sur, Router principal, Banco de baterías…')
                            ->helperText('Es el nombre que sale en los avisos cuando una lectura suya es rara.'),
                        Select::make('hardware_device_id')
                            ->relationship('hardwareDevice', 'name')
                            ->required()->searchable()->preload()
                            ->label('Dispositivo monitor')
                            ->helperText('El aparato que mide.'),
                        Select::make('hardware_device_monitorized_id')
                            ->relationship('monitorized', 'name')
                            ->required()->searchable()->preload()
                            ->label('Dispositivo monitorizado')
                            ->helperText('El aparato medido. Las lecturas se guardan contra éste, no contra el monitor.'),
                        TextInput::make('sensor_position')
                            ->numeric()->minValue(0)->required()
                            ->label('Canal del monitor')
                            ->helperText('Tiene que coincidir con el «pos» que manda el dispositivo en cada lectura.'),
                    ])->columns(2),

                Section::make('Instalación y papel')
                    ->schema([
                        Select::make('energy_system_id')
                            ->relationship('system', 'name')
                            ->searchable()->preload()
                            ->label('Instalación')
                            ->helperText('Lo que permite preguntar «cuánto ha generado la casa hoy».'),
                        Select::make('energy_source_type_id')
                            ->relationship('sourceType', 'name')
                            ->searchable()->preload()
                            ->label('Tipo de fuente'),
                        Select::make('role')
                            ->options([
                                HardwareEnergy::ROLE_GENERATOR => 'Generador',
                                HardwareEnergy::ROLE_LOAD => 'Consumo',
                                HardwareEnergy::ROLE_STORAGE => 'Batería',
                            ])
                            ->default(HardwareEnergy::ROLE_LOAD)
                            ->required()
                            ->label('Papel')
                            ->helperText('Los totales de generación de una instalación cuentan sólo los generadores.'),
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Activo')
                            ->helperText('Un elemento retirado deja de aceptar lecturas nuevas.'),
                        Toggle::make('is_generator')
                            ->label('Es generador (columna antigua)')
                            ->helperText('Se conserva mientras se migra del todo a «Papel». No la uses para nada nuevo.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Características eléctricas')
                    ->description('La tensión nominal es la que arregla el cálculo de los vatios cuando la medida falta o no es creíble.')
                    ->schema([
                        TextInput::make('nominal_voltage')
                            ->numeric()->step(0.01)->suffix(' V')
                            ->label('Tensión nominal'),
                        TextInput::make('rated_power_w')
                            ->numeric()->step(0.01)->suffix(' W')
                            ->label('Potencia nominal'),
                        TextInput::make('voltage_min')
                            ->numeric()->step(0.01)->suffix(' V')
                            ->label('Tensión mínima creíble')
                            ->helperText('Por debajo de esto, la medida se descarta y se usa la nominal.'),
                        TextInput::make('voltage_max')
                            ->numeric()->step(0.01)->suffix(' V')
                            ->label('Tensión máxima creíble'),
                        TextInput::make('capacity_mah')
                            ->numeric()->step(0.01)->suffix(' mAh')
                            ->label('Capacidad'),
                        TextInput::make('capacity_wh')
                            ->numeric()->step(0.01)->suffix(' Wh')
                            ->label('Capacidad'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Elemento')
                    ->description(fn (HardwareEnergy $record): string => $record->monitorized->name ?? '')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('system.name')
                    ->label('Instalación')
                    ->badge()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'Generador',
                        HardwareEnergy::ROLE_STORAGE => 'Batería',
                        default => 'Consumo',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'success',
                        HardwareEnergy::ROLE_STORAGE => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('sourceType.name')
                    ->label('Fuente')
                    ->toggleable(),
                TextColumn::make('hardwareDevice.name')
                    ->label('Monitor')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->numeric()
                    ->sortable(),
                // Sin tensión nominal los vatios de este elemento dependen del
                // voltaje que traiga la petición, que puede no ser el suyo.
                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('sin definir')
                    ->color(fn ($state): string => $state === null ? 'danger' : 'gray')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('energy_system_id')
                    ->relationship('system', 'name')
                    ->label('Instalación'),
                SelectFilter::make('role')
                    ->options([
                        HardwareEnergy::ROLE_GENERATOR => 'Generador',
                        HardwareEnergy::ROLE_LOAD => 'Consumo',
                        HardwareEnergy::ROLE_STORAGE => 'Batería',
                    ])
                    ->label('Papel'),
                TernaryFilter::make('is_active')->label('Activo'),
                TernaryFilter::make('nominal_voltage')
                    ->label('Tensión nominal')
                    ->placeholder('Todos')
                    ->trueLabel('Con tensión nominal')
                    ->falseLabel('SIN tensión nominal')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('nominal_voltage'),
                        false: fn ($query) => $query->whereNull('nominal_voltage'),
                        blank: fn ($query) => $query,
                    ),
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
