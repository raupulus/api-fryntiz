<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareEnergies;

use App\Models\Hardware\HardwareEnergy;
use Closure;
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
    public static function forADevice(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Qué mide')
                ->description('Este elemento se mide a sí mismo. Para medir OTRO aparato, dalo de alta desde «Elementos de Energía».')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::channel(),
                    self::source(),
                    self::active(),
                ]),

            self::electricalCharacteristics(),
        ]);
    }

    /**
     * Desde Elementos de Energía: aquí sí se elige qué se mide y con qué papel.
     */
    public static function full(Schema $schema): Schema
    {
        return $schema->components([
            // A ancho completo y arriba del todo: el medidor es lo único que no
            // cambia entre los papeles de un mismo aparato, así que es la
            // cabecera de la ficha y no una columna más.
            Section::make('El aparato que mide')
                ->description('No cambia entre los papeles de este aparato. Todo lo de abajo sí: cada canal mide una cosa, con su fuente y su tensión.')
                ->columnSpanFull()
                ->schema([
                    self::meter(),
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
                    self::role(),
                    self::channel(),
                    self::active(),
                ]),

            Section::make('Fuente de energía')
                ->description('Opcional. Sólo para reconocer el canal en los listados.')
                ->columns(2)
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    self::source(),
                ]),

            self::electricalCharacteristics(),
        ]);
    }

    /**
     * Desde otro papel del mismo aparato: el medidor no se pregunta, que es el
     * que se está mirando.
     */
    public static function forSameMeter(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Qué mide este canal')
                ->description('El medidor no se pregunta: es el mismo aparato.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('hardware_device_monitorized_id')
                        ->relationship('monitorized', 'name')
                        ->required()->searchable()->preload()
                        ->label('Dispositivo monitorizado')
                        ->helperText('El aparato medido. Las lecturas se guardan contra éste, no contra el monitor.'),
                    self::role(),
                    self::channel(),
                    self::active(),
                ]),

            Section::make('Fuente de energía')
                ->description('Opcional. Sólo para reconocer el canal en los listados.')
                ->columns(2)
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    self::source(),
                ]),

            self::electricalCharacteristics(),
        ]);
    }

    /**
     * El aparato que mide se elige **al crear y nunca más**.
     *
     * Cambiarlo en un elemento que ya existe deja sus lecturas, sus resúmenes y
     * su acumulado atribuidos a un medidor que nunca los tomó, y no hay forma
     * de deshacerlo: en la telemetría no queda constancia de quién la midió
     * aparte de esta columna. Además el medidor es parte del índice único junto
     * al medido, el papel y el canal.
     *
     * Si está mal, se da de baja el elemento y se crea el bueno.
     */
    private static function meter(): Select
    {
        return Select::make('hardware_device_id')
            ->relationship('hardwareDevice', 'name')
            ->required()->searchable()->preload()
            ->label('Dispositivo monitor')
            ->disabled(fn (?HardwareEnergy $record): bool => $record !== null)
            ->helperText(fn (?HardwareEnergy $record): string => $record !== null
                ? 'No se puede cambiar: sus lecturas y acumulados están tomados por este aparato. Si está mal, da de baja el elemento y crea el bueno.'
                : 'El aparato que hace la medida. Se elige ahora y no se puede cambiar después.')
            ->columnSpanFull();
    }

    /**
     * El papel se elige **al crear y nunca más**.
     *
     * Cambiarlo en un elemento que ya existe no lo convierte en otra cosa: deja
     * sus lecturas, sus resúmenes diarios y su acumulado de años contando algo
     * que ya no es —la generación de un panel pasa a figurar como consumo— y no
     * hay forma de deshacerlo. Además el papel es parte del índice único junto
     * al medidor, el medido y el canal, así que moverlo puede chocar con otro
     * elemento existente.
     *
     * Si un elemento está dado de alta con el papel equivocado, lo que se hace
     * es darlo de baja y crear el bueno, que es lo único que no miente sobre lo
     * que hay medido.
     */
    private static function role(): Select
    {
        return Select::make('role')
            ->options(HardwareEnergy::ROLE_LABELS)
            ->default(HardwareEnergy::ROLE_LOAD)
            ->required()
            ->live()
            ->label('Papel')
            ->disabled(fn (?HardwareEnergy $record): bool => $record !== null)
            ->helperText(fn (?HardwareEnergy $record): string => $record !== null
                ? 'No se puede cambiar: sus lecturas y acumulados ya están contados como este papel. Si está mal, da de baja el elemento y crea el bueno.'
                : 'Generador es lo que produce, consumo lo que gasta y batería lo que almacena. Se elige ahora y no se puede cambiar después.');
    }

    /**
     * El canal del sensor.
     *
     * **Sólo distingue consumos.** La ingesta busca el generador y la batería
     * por su papel, sin mirar el canal —de cada uno hay uno—, así que ahí el
     * campo no hace nada y preguntarlo sólo confunde: se fija a 0 y se explica.
     * En los consumos sí manda: es lo que reparte los tres canales de un INA
     * entre los tres aparatos que mide.
     */
    private static function channel(): TextInput
    {
        return TextInput::make('sensor_position')
            ->numeric()->minValue(0)->default(0)->required()
            ->label('Canal del monitor')
            ->helperText(fn (Get $get): string => self::isLoad($get)
                ? 'El `channel` que manda el dispositivo en cada lectura de consumo. 0 si sólo tiene un canal.'
                : 'De generador y de batería sólo hay uno por aparato, así que la ingesta no mira el canal. Déjalo en 0.')
            ->disabled(fn (Get $get): bool => ! self::isLoad($get))
            ->dehydrated()
            // Sin esto, repetir medidor + medido + papel + canal chocaba contra
            // `hardware_energy_device_monitorized_role_position_unique` y el
            // panel devolvía una pantalla de error en vez de decir qué pasa.
            ->rule(static fn (Get $get, ?HardwareEnergy $record): Closure => static function (string $atributo, mixed $valor, Closure $falla) use ($get, $record): void {
                if (self::channelIsTaken($get, $record, $valor)) {
                    $falla('Ya hay un elemento de este papel en ese canal para el mismo par monitor/monitorizado.');
                }
            });
    }

    /**
     * ¿Hay ya otro elemento con esta misma combinación?
     *
     * Los cuatro campos del índice único se resuelven desde el formulario
     * cuando están, y desde el registro que se está editando cuando el
     * formulario no los pregunta —la ficha del dispositivo no pregunta ni el
     * medidor ni el papel—.
     */
    private static function channelIsTaken(Get $get, ?HardwareEnergy $registro, mixed $canal): bool
    {
        $medidor = $get('hardware_device_id') ?? $registro?->hardware_device_id;
        $medido = $get('hardware_device_monitorized_id') ?? $registro?->hardware_device_monitorized_id;
        $papel = $get('role') ?? $registro?->role;

        if ($medidor === null || $medido === null || $papel === null || $canal === null || $canal === '') {
            return false;
        }

        return HardwareEnergy::query()
            ->withTrashed()
            ->where('hardware_device_id', $medidor)
            ->where('hardware_device_monitorized_id', $medido)
            ->where('role', $papel)
            ->where('sensor_position', (int) $canal)
            ->when($registro !== null, static fn ($q) => $q->whereKeyNot($registro->getKey()))
            ->exists();
    }

    /**
     * ¿Este elemento es un consumo?
     *
     * En la ficha del dispositivo el papel no está en el formulario —lo pone el
     * botón que se ha pulsado—, así que ahí no hay `role` que mirar y se trata
     * como consumo, que es el único papel con varios canales.
     */
    private static function isLoad(Get $get): bool
    {
        $role = $get('role');

        return $role === null || $role === HardwareEnergy::ROLE_LOAD;
    }

    /**
     * De dónde viene la energía de este canal.
     *
     * **Es una etiqueta, no un cálculo.** No entra en ninguna cuenta ni cambia
     * nada de lo que se guarda: sirve para saber, mirando el listado, si un
     * canal cuelga del panel, de la red o de una batería que se carga a mano.
     * Por eso se puede dejar vacío.
     *
     * Es de cada canal y no del aparato porque un mismo medidor puede tener
     * canales alimentados de sitios distintos: una Raspberry con un INA puede
     * medir un ventilador que va del panel y un router que va de la red.
     */
    private static function source(): Select
    {
        return Select::make('energy_source_type_id')
            ->relationship('sourceType', 'name')
            ->searchable()->preload()
            ->label('De dónde viene la energía de este canal')
            ->placeholder('Sin especificar')
            ->helperText(
                'Sólo es una etiqueta para reconocerlo en los listados: no entra '
                .'en ningún cálculo y se puede dejar vacío. Ponlo si este canal '
                .'cuelga de algo distinto a los demás del mismo aparato — por '
                .'ejemplo un canal alimentado de la red en un montaje solar.'
            );
    }

    private static function active(): Toggle
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
    private static function electricalCharacteristics(): Section
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
                    ->label('Potencia nominal')
                    ->helperText('Potencia de diseño/catálogo (W).'),
                TextInput::make('voltage_min')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label(fn (Get $get): string => self::isBattery($get) ? 'Tensión a 0 % de carga' : 'Tensión mínima esperada')
                    ->helperText(fn (Get $get): string => self::isBattery($get)
                        ? 'Con ésta y la de 100 % se calcula el porcentaje de carga cuando el aparato no lo manda. Pon las del banco real, no un rango ancho.'
                        : 'Sólo para avisar: una medida por debajo se guarda igual, con un aviso en la respuesta. Vacío = no se avisa nunca.'),
                TextInput::make('voltage_max')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label(fn (Get $get): string => self::isBattery($get) ? 'Tensión a 100 % de carga' : 'Tensión máxima esperada'),

                // Sólo tienen sentido en una batería.
                TextInput::make('capacity_ah')
                    ->numeric()->step(0.001)->suffix(' Ah')
                    ->label('Capacidad nominal (Ah)')
                    ->helperText('Capacidad nominal en amperios-hora (resolución hasta 1 mAh).')
                    ->visible(fn (Get $get): bool => self::isBattery($get)),
                TextInput::make('default_interval_seconds')
                    ->numeric()->minValue(1)->default(60)->required()->suffix(' s')
                    ->label('Intervalo supuesto sin «duration»')
                    ->helperText(
                        'Segundos que se suponen entre lecturas cuando la subida no manda `duration`. '
                        .'Ponle cada cuánto sube este cacharro: si sube cada 10 minutos y aquí hay 60, '
                        .'se registrará la sexta parte de la energía real. '
                        .'Lo mejor es que el aparato mande `duration` en cada subida; esto es el respaldo.'
                    ),
                Toggle::make('auto_calculate_history')
                    ->label('Rehacer el acumulado cada noche')
                    ->helperText(
                        'Actívalo si los totales de este elemento los calculamos nosotros sumando sus lecturas. '
                        .'Apágalo si el aparato lleva su propio contador de por vida, como el Renogy Rover: '
                        .'ahí el acumulado es suyo y el cron no debe tocarlo.'
                    )
                    ->default(true),
            ]);
    }

    /**
     * ¿Este elemento es una batería?
     *
     * En la ficha del dispositivo el papel no está en el formulario —lo pone el
     * botón—, así que ahí no hay `role` que mirar y los campos de capacidad se
     * enseñan igual: es preferible enseñar un campo de más que esconder el de
     * la capacidad justo al dar de alta el banco de baterías.
     */
    private static function isBattery(Get $get): bool
    {
        $role = $get('role');

        return $role === null || $role === HardwareEnergy::ROLE_BATTERY;
    }
}
