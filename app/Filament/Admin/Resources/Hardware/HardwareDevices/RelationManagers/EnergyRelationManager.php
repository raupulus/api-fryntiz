<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices\RelationManagers;

use App\Filament\Admin\Resources\Hardware\HardwareEnergies\HardwareEnergyForm;
use App\Models\Hardware\HardwareDevice;
use App\Models\Hardware\HardwareEnergy;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Los papeles energéticos del dispositivo, desde su propia ficha.
 *
 * **El agujero que tapa.** Para dar de alta la energía de un cacharro había que
 * irse a otra pantalla, saber qué papel le tocaba, acordarse del canal y
 * repetirlo por cada papel. Y nada impedía crear cuatro generadores del mismo
 * aparato: no había restricción ni en la base de datos ni en el formulario.
 *
 * Aquí hay **un botón por papel**, con lo que hace cada uno escrito al lado. Los
 * de generador y batería desaparecen en cuanto existe el suyo, porque de eso hay
 * uno; el de consumo se queda, porque un monitor mide tantas cargas como canales
 * tenga.
 *
 * **Lo que se cree desde aquí se mide a sí mismo:**
 * `hardware_device_monitorized_id = hardware_device_id`. Para medir *otro*
 * aparato está la pantalla de Elementos de Energía, y así este formulario se
 * queda en los campos que de verdad hay que rellenar.
 */
class EnergyRelationManager extends RelationManager
{
    protected static string $relationship = 'hardwareEnergy';

    protected static ?string $title = 'Energía';

    protected static ?string $modelLabel = 'elemento';

    protected static ?string $pluralModelLabel = 'elementos de energía';

    public function form(Schema $schema): Schema
    {
        return HardwareEnergyForm::deUnDispositivo($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(
                'Qué papel juega este aparato en la instalación eléctrica. Puede '
                .'tener varios a la vez: un controlador solar mide lo que entra '
                .'del panel, lo que sale hacia la carga y lo que hay en la batería.'
            )
            ->modifyQueryUsing(fn ($query) => $query->with(['monitorized', 'system', 'sourceType']))
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
                TextColumn::make('monitorized.display_name')
                    ->label('Qué mide')
                    ->placeholder('a sí mismo'),
                TextColumn::make('sensor_position')
                    ->label('Canal')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('nominal_voltage')
                    ->label('V nominal')
                    ->suffix(' V')
                    ->placeholder('la que reporte'),
                TextColumn::make('system.name')
                    ->label('Instalación')
                    ->placeholder('sin asignar')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->headerActions($this->botonesDeAlta())
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Este aparato no mide energía')
            ->emptyStateDescription(
                'Si mide algo —o si es él quien la produce, la gasta o la '
                .'almacena—, dalo de alta con uno de los botones de arriba.'
            );
    }

    /**
     * Un botón por papel, y sólo mientras quepa otro de ese papel.
     *
     * @return list<CreateAction>
     */
    private function botonesDeAlta(): array
    {
        $descripciones = [
            HardwareEnergy::ROLE_GENERATOR => 'Lo que produce: el panel solar, el alternador…',
            HardwareEnergy::ROLE_LOAD => 'Lo que gasta: la salida de carga, un router, una Raspberry…',
            HardwareEnergy::ROLE_BATTERY => 'Lo que almacena: el banco de baterías.',
        ];

        $iconos = [
            HardwareEnergy::ROLE_GENERATOR => 'heroicon-o-sun',
            HardwareEnergy::ROLE_LOAD => 'heroicon-o-bolt',
            HardwareEnergy::ROLE_BATTERY => 'heroicon-o-battery-50',
        ];

        $colores = [
            HardwareEnergy::ROLE_GENERATOR => 'success',
            HardwareEnergy::ROLE_LOAD => 'info',
            HardwareEnergy::ROLE_BATTERY => 'warning',
        ];

        $acciones = [];

        foreach (HardwareEnergy::ROLES as $rol) {
            $acciones[] = CreateAction::make('crear_'.$rol)
                ->label(HardwareEnergy::ETIQUETAS_DE_ROL[$rol])
                ->icon($iconos[$rol])
                ->color($colores[$rol])
                ->modalHeading(HardwareEnergy::ETIQUETAS_DE_ROL[$rol].' de este aparato')
                ->modalDescription($descripciones[$rol])
                // El papel no se pregunta: lo dice el botón que se ha pulsado.
                // Y se mide a sí mismo, que es lo que significa darlo de alta
                // desde su propia ficha.
                ->mutateDataUsing(function (array $data) use ($rol): array {
                    $data['role'] = $rol;
                    $data['hardware_device_monitorized_id'] = $this->getOwnerRecord()->getKey();

                    return $data;
                })
                ->visible(fn (): bool => $this->cabeOtro($rol));
        }

        return $acciones;
    }

    /**
     * ¿Queda sitio para otro elemento de este papel?
     *
     * De generador y de batería hay uno; de consumo, tantos como canales tenga
     * el medidor. El límite vive en {@see HardwareEnergy::LIMITE_POR_ROL}.
     */
    private function cabeOtro(string $rol): bool
    {
        $limite = HardwareEnergy::LIMITE_POR_ROL[$rol] ?? null;

        if ($limite === null) {
            return true;
        }

        /** @var HardwareDevice $device */
        $device = $this->getOwnerRecord();

        return $device->hardwareEnergy()->where('role', $rol)->count() < $limite;
    }
}
