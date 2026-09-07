<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\EnergySystems\RelationManagers;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyForm;
use App\Models\Hardware\HardwareEnergy;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Los elementos del controlador solar de esta instalación.
 *
 * Un controlador solar es un aparato con los tres papeles a la vez: lo que entra
 * del panel, lo que sale hacia la carga y lo que hay en la batería. Aquí se ven
 * y se crean los tres, con las mismas reglas que en la ficha de un dispositivo
 * —de generador y de batería hay uno, de consumo los que hagan falta.
 *
 * **Sólo los del controlador.** Una instalación agrupa también las cargas que
 * cuelgan de ella —la Raspberry, el portátil—, pero ésas se gestionan en
 * «Elementos de Energía»: aquí saldrían mezcladas con las del propio aparato y
 * no se distinguiría una cosa de la otra.
 */
class ElementsRelationManager extends RelationManager
{
    protected static string $relationship = 'elements';

    protected static ?string $title = 'Elementos del controlador';

    protected static ?string $modelLabel = 'elemento';

    protected static ?string $pluralModelLabel = 'elementos';

    public function form(Schema $schema): Schema
    {
        return HardwareEnergyForm::completo($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(
                'Lo que mide el controlador solar de esta instalación. Las cargas '
                .'que cuelgan de ella —una Raspberry, un portátil— se gestionan en '
                .'«Elementos de Energía».'
            )
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['monitorized', 'hardwareDevice', 'sourceType'])
                // Los del propio controlador, no los de todo lo que cuelga de
                // la instalación.
                ->whereHas('hardwareDevice', fn (Builder $q) => $q->whereHas(
                    'type',
                    fn (Builder $t) => $t->where('slug', 'controlador-solar'),
                )))
            ->columns([
                TextColumn::make('role')
                    ->label('Papel')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => HardwareEnergy::ETIQUETAS_DE_ROL[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        HardwareEnergy::ROLE_GENERATOR => 'success',
                        HardwareEnergy::ROLE_BATTERY => 'warning',
                        default => 'info',
                    }),
                TextColumn::make('hardwareDevice.display_name')
                    ->label('Controlador'),
                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('la que reporte'),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Añadir elemento'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin elementos del controlador')
            ->emptyStateDescription(
                'Da de alta lo que mide: la entrada del panel, la salida de carga '
                .'y la batería.'
            );
    }
}
