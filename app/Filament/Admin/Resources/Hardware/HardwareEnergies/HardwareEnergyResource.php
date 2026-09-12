<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies;

use App\Filament\Admin\Clusters\Energy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\CreateHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\EditHardwareEnergy;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\Pages\ListHardwareEnergies;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Hardware\HardwareEnergy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
    use ScopesToOwner;

    /**
     * Resuelve la pertenencia a través del dispositivo medidor (hardwareDevice).
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    protected static function scopeOwnerQuery(Builder $query, int $userId): Builder
    {
        return $query->whereHas('hardwareDevice', static fn (Builder $q) => $q->where('user_id', $userId));
    }

    protected static ?string $model = HardwareEnergy::class;

    protected static ?string $cluster = Energy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBattery100;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Elemento energético';

    protected static ?string $pluralModelLabel = 'Elementos de energía';

    public static function form(Schema $schema): Schema
    {
        return HardwareEnergyForm::full($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Agrupado por el aparato que mide, que es lo único que no cambia
            // entre las filas de un mismo medidor.
            ->defaultGroup('hardwareDevice.name')
            ->groups([
                Group::make('hardwareDevice.name')
                    ->label('Dispositivo monitor')
                    ->collapsible(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'monitorized', 'hardwareDevice', 'sourceType',
            ]))
            ->defaultSort('sensor_position')
            ->columns([
                TextColumn::make('display_name')
                    ->label('Elemento')
                    ->description(fn (HardwareEnergy $record): string => $record->hardware_device_id === null
                        ? ''
                        : (string) $record->getRelationValue('hardwareDevice')?->display_name)
                    ->searchable(['sensor_position'])
                    ->sortable(false),
                TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => HardwareEnergy::ROLE_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'success',
                        HardwareEnergy::ROLE_BATTERY => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('monitorized.display_name')
                    ->label('Qué mide')
                    ->placeholder('a sí mismo'),
                TextColumn::make('sourceType.name')
                    ->label('Fuente')
                    ->placeholder('sin asignar')
                    ->toggleable(),
                TextColumn::make('hardwareDevice.name')
                    ->label('Monitor')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('sin definir')
                    ->color(fn ($state): string => $state === null ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('capacity_ah')
                    ->label('Capacidad')
                    ->suffix(' Ah')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('capacity_wh')
                    ->label('Capacidad Wh')
                    ->suffix(' Wh')
                    ->placeholder('—'),
                IconColumn::make('auto_calculate_history')
                    ->label('Auto Hist.')
                    ->boolean()
                    ->toggleable(),
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
                SelectFilter::make('role')
                    ->options(HardwareEnergy::ROLE_LABELS)
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
                TernaryFilter::make('auto_calculate_history')
                    ->label('Auto Histórico'),
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
        // Los papeles primero: es lo que se viene a hacer aquí. Las otras tres
        // son las lecturas, agregados diarios e históricos que ha mandado el aparato.
        return [
            RelationManagers\RolesRelationManager::class,
            RelationManagers\ReadingsRelationManager::class,
            RelationManagers\TodayRelationManager::class,
            RelationManagers\HistoricalRelationManager::class,
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
