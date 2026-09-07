<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies;

use App\Models\Hardware\HardwareEnergy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Los campos de un elemento de energía, en un sitio.
 *
 * Se pinta desde tres pantallas —la ficha del dispositivo, la de la instalación
 * y la de Elementos de Energía— y en las tres son los mismos campos con las
 * mismas ayudas. Tenerlo copiado tres veces es la forma de que acaben diciendo
 * cosas distintas.
 *
 * **Qué es común y qué no.** De lo único que se puede dar por hecho que no
 * cambia entre las filas de un mismo medidor es **el propio medidor**. El
 * aparato medido, la instalación, la fuente y el `is_active` son **de cada
 * canal**: una Raspberry con un INA puede llevar la batería de 12 V a un
 * ventilador, la de litio a una lámpara y el cargador de red a un
 * microcontrolador. Tres canales, tres cosas medidas, tres fuentes.
 */
class HardwareEnergyForm
{
    /**
     * Desde la ficha de un dispositivo: sin preguntar el medidor, el medido ni
     * el papel, porque los tres los pone el contexto.
     */
    public static function deUnDispositivo(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Qué mide')
                ->description('Este elemento se mide a sí mismo. Para medir OTRO aparato, dalo de alta desde «Elementos de Energía».')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::canal(),
                    self::instalacion(),
                    self::fuente(),
                    self::activo(),
                ]),

            self::caracteristicasElectricas(),
        ]);
    }

    /**
     * Desde Elementos de Energía: aquí sí se elige qué se mide y con qué papel.
     */
    public static function completo(Schema $schema): Schema
    {
        return $schema->components([
            // A ancho completo y arriba del todo: el medidor es lo único que no
            // cambia entre los papeles de un mismo aparato, así que es la
            // cabecera de la ficha y no una columna más.
            Section::make('El aparato que mide')
                ->description('No cambia entre los papeles de este aparato. Todo lo de abajo sí: cada canal mide una cosa, con su fuente y su tensión.')
                ->columnSpanFull()
                ->schema([
                    Select::make('hardware_device_id')
                        ->relationship('hardwareDevice', 'name')
                        ->required()->searchable()->preload()
                        ->label('Dispositivo monitor')
                        ->helperText('El aparato que mide.')
                        ->columnSpanFull(),
                ]),

            Section::make('Qué mide este canal')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('hardware_device_monitorized_id')
                        ->relationship('monitorized', 'name')
                        ->required()->searchable()->preload()
                        ->label('Dispositivo monitorizado')
                        ->helperText('El aparato medido. Las lecturas se guardan contra éste, no contra el monitor.'),
                    self::papel(),
                    self::canal(),
                    self::activo(),
                ]),

            Section::make('Instalación')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::instalacion(),
                    self::fuente(),
                ]),

            self::caracteristicasElectricas(),
        ]);
    }

    /**
     * Desde otro papel del mismo aparato: el medidor no se pregunta, que es el
     * que se está mirando.
     */
    public static function delMismoMedidor(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Qué mide este canal')
                ->description('El aparato que mide es el mismo del que vienes.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('hardware_device_monitorized_id')
                        ->relationship('monitorized', 'name')
                        ->required()->searchable()->preload()
                        ->label('Dispositivo monitorizado')
                        ->helperText('El aparato medido. Las lecturas se guardan contra éste, no contra el monitor.'),
                    self::canal(),
                    self::activo(),
                ]),

            Section::make('Instalación')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::instalacion(),
                    self::fuente(),
                ]),

            self::caracteristicasElectricas(),
        ]);
    }

    private static function papel(): Select
    {
        return Select::make('role')
            ->options(HardwareEnergy::ETIQUETAS_DE_ROL)
            ->default(HardwareEnergy::ROLE_LOAD)
            ->required()
            ->live()
            ->label('Papel')
            ->helperText('Generador es lo que produce, consumo lo que gasta y batería lo que almacena.');
    }

    private static function canal(): TextInput
    {
        return TextInput::make('sensor_position')
            ->numeric()->minValue(0)->default(0)->required()
            ->label('Canal del monitor')
            ->helperText('Tiene que coincidir con el «pos» que manda el dispositivo en cada lectura. 0 si sólo tiene uno.');
    }

    private static function instalacion(): Select
    {
        return Select::make('energy_system_id')
            ->relationship('system', 'name')
            ->searchable()->preload()
            ->label('Instalación')
            ->helperText('Lo que permite preguntar «cuánto ha generado la casa hoy».');
    }

    private static function fuente(): Select
    {
        return Select::make('energy_source_type_id')
            ->relationship('sourceType', 'name')
            ->searchable()->preload()
            ->label('Tipo de fuente')
            ->helperText('De dónde sale la energía de ESTE canal, que puede no ser la misma que la del de al lado.');
    }

    private static function activo(): Toggle
    {
        return Toggle::make('is_active')
            ->default(true)
            ->label('Activo')
            ->helperText('Apágalo para dejar de recoger lecturas de este canal sin borrar lo que ya hay.');
    }

    /**
     * La tensión y la capacidad, que son de cada papel: en un controlador solar,
     * el generador mide el panel y el consumo mide la batería.
     */
    private static function caracteristicasElectricas(): Section
    {
        return Section::make('Características eléctricas')
            ->description('Para calcular manda siempre la tensión que reporte el aparato en cada lectura. Lo de aquí es el respaldo para cuando no la mande.')
            ->columns(2)
            ->columnSpanFull()
            ->collapsed()
            ->schema([
                TextInput::make('nominal_voltage')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label('Tensión nominal')
                    ->helperText('La de ESTE lado: en un controlador solar, el panel y la batería no están a la misma.'),
                TextInput::make('rated_power_w')
                    ->numeric()->step(0.01)->suffix(' W')
                    ->label('Potencia nominal'),
                TextInput::make('voltage_min')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label('Tensión mínima creíble')
                    ->helperText('Por debajo de esto se descarta la medida y se usa la nominal. Vacío = se acepta lo que llegue.'),
                TextInput::make('voltage_max')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label('Tensión máxima creíble'),

                // Sólo tienen sentido en una batería.
                TextInput::make('capacity_mah')
                    ->numeric()->step(0.01)->suffix(' mAh')
                    ->label('Capacidad')
                    ->visible(fn (Get $get): bool => self::esBateria($get)),
                TextInput::make('capacity_wh')
                    ->numeric()->step(0.01)->suffix(' Wh')
                    ->label('Capacidad')
                    ->visible(fn (Get $get): bool => self::esBateria($get)),
            ]);
    }

    /**
     * En la ficha del dispositivo el papel no está en el formulario —lo pone el
     * botón—, así que ahí no hay `role` que mirar y los campos de capacidad se
     * enseñan igual.
     */
    private static function esBateria(Get $get): bool
    {
        $rol = $get('role');

        return $rol === null || $rol === HardwareEnergy::ROLE_BATTERY;
    }
}
