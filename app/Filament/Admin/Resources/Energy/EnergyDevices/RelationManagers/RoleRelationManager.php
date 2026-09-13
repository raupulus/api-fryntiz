<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Energy\EnergyDevices\RelationManagers;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyForm;
use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyResource;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Una pestaña por papel dentro de la ficha energética del aparato.
 *
 * **Por qué una por papel y no una lista con los tres.** Una tabla con el
 * generador, la batería y los consumos mezclados obliga a leer la columna
 * «Papel» en cada fila para saber qué estás mirando, y el botón de crear tiene
 * que preguntar el papel. Con una pestaña por papel, lo que estás mirando lo
 * dice la pestaña y el botón de crear ya sabe qué crea.
 *
 * Cada pestaña enseña **la configuración del elemento y su estado**: no hay que
 * salir de aquí para saber si un canal tiene tensión nominal, cuánta energía
 * lleva hoy o cuándo mandó la última lectura.
 */
abstract class RoleRelationManager extends RelationManager
{
    protected static string $relationship = 'hardwareEnergy';

    /**
     * El papel del que se ocupa esta pestaña.
     */
    abstract protected static function role(): string;

    /**
     * Qué es este papel, en una línea, para quien no lo tenga claro.
     */
    abstract protected static function explicacion(): string;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return HardwareEnergy::ROLE_LABELS_PLURAL[static::role()] ?? static::role();
    }

    public function form(Schema $schema): Schema
    {
        // El del dispositivo: aquí el medidor es el aparato que se está mirando
        // y el papel lo pone la pestaña, así que no se preguntan.
        return HardwareEnergyForm::forADevice($schema);
    }

    public function table(Table $table): Table
    {
        $papel = static::role();

        return $table
            ->description(static::explicacion())
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('role', $papel)
                ->with(['monitorized', 'sourceType']))
            ->defaultSort('sensor_position')
            ->columns([
                TextColumn::make('monitorized.display_name')
                    ->label('Qué mide')
                    ->placeholder('a sí mismo'),

                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->badge()
                    ->color('gray')
                    // De generador y batería sólo hay uno y la ingesta no mira
                    // el canal: enseñarlo ahí es ruido.
                    ->visible($papel === HardwareEnergy::ROLE_LOAD),

                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('SIN DEFINIR')
                    ->color(fn ($state): string => $state === null ? 'danger' : 'gray'),

                TextColumn::make('capacity_ah')
                    ->label('Capacidad')
                    ->suffix(' Ah')
                    ->placeholder('—')
                    ->visible($papel === HardwareEnergy::ROLE_BATTERY),

                TextColumn::make('default_interval_seconds')
                    ->label('Intervalo')
                    ->suffix(' s')
                    ->tooltip('Segundos que se suponen entre lecturas cuando la subida no trae «duration».')
                    ->toggleable(),

                IconColumn::make('auto_calculate_history')
                    ->label('Auto hist.')
                    ->tooltip('Si el cierre nocturno rehace su acumulado. Apagado en los aparatos que llevan su propio contador.')
                    ->boolean()
                    ->toggleable(),

                // El estado, para no tener que entrar en la telemetría sólo
                // para saber si el canal está vivo.
                TextColumn::make('today_sum')
                    ->label('Hoy')
                    ->state(fn (HardwareEnergy $record): ?float => $record->today()
                        ->whereDate('date', now('UTC')->toDateString())
                        ->value('energy_wh'))
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' Wh')
                    ->placeholder('—'),

                TextColumn::make('lifetime_sum')
                    ->label('De por vida')
                    ->state(fn (HardwareEnergy $record): ?float => $record->historical()->sum('energy_wh') ?: null)
                    ->numeric(decimalPlaces: 0)
                    ->suffix(' Wh')
                    ->placeholder('—'),

                TextColumn::make('last_reading')
                    ->label('Última lectura')
                    ->state(fn (HardwareEnergy $record) => $record->readings()->max('created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('nunca'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir '.mb_strtolower(HardwareEnergy::ROLE_LABELS[$papel] ?? $papel))
                    ->modalHeading(HardwareEnergy::ROLE_LABELS[$papel].' de este aparato')
                    ->modalDescription(static::explicacion())
                    // El papel lo pone la pestaña, y lo que se da de alta aquí
                    // se mide a sí mismo: para medir OTRO aparato está Elementos
                    // de energía.
                    ->mutateDataUsing(function (array $data) use ($papel): array {
                        $data['role'] = $papel;
                        $data['hardware_device_monitorized_id'] ??= $this->getOwnerRecord()->getKey();

                        return $data;
                    })
                    ->visible(fn (): bool => $this->cabeOtro()),
            ])
            ->recordActions([
                Action::make('telemetria')
                    ->label('Telemetría')
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->url(fn (HardwareEnergy $record): string => HardwareEnergyResource::getUrl('edit', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->emptyStateHeading('Sin '.mb_strtolower(HardwareEnergy::ROLE_LABELS_PLURAL[$papel] ?? $papel))
            ->emptyStateDescription(static::explicacion());
    }

    /**
     * ¿Queda sitio para otro de este papel?
     *
     * De generador y de batería hay uno; de consumo, tantos como canales tenga
     * el medidor. El límite vive en {@see HardwareEnergy::LIMIT_PER_ROLE}.
     */
    private function cabeOtro(): bool
    {
        $limite = HardwareEnergy::LIMIT_PER_ROLE[static::role()] ?? null;

        if ($limite === null) {
            return true;
        }

        /** @var HardwareDevice $aparato */
        $aparato = $this->getOwnerRecord();

        return $aparato->hardwareEnergy()->where('role', static::role())->count() < $limite;
    }
}
