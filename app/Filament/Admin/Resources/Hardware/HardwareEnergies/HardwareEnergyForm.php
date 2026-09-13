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
                        ->helperText(
                            'Qué cacharro hay enchufado a este canal. Si el aparato se mide a sí mismo '
                            .'—un controlador solar midiendo su propio panel—, es él mismo.'
                        ),
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
                        ->helperText(
                            'Qué cacharro hay enchufado a este canal. Si el aparato se mide a sí mismo '
                            .'—un controlador solar midiendo su propio panel—, es él mismo.'
                        ),
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
                ? 'Qué entrada del medidor es ésta. Un sensor con tres pinzas manda «channel: 0», «channel: 1» y «channel: 2» '
                  .'en cada subida, y este número es el que dice cuál de las tres es este canal. '
                  .'Si el aparato sólo mide una cosa, déjalo en 0.'
                : 'De generador y de batería sólo hay uno por aparato, así que este número no se usa para nada. Déjalo en 0.')
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
            ->helperText(
                'Apágalo para que las subidas de este canal dejen de guardarse. '
                .'No se borra nada de lo que ya hay ni se pierde el histórico: es para cuando desconectas '
                .'algo del medidor y no quieres que siga apuntando ceros.'
            );
    }

    /**
     * La tensión y la capacidad, que son de cada papel: en un controlador solar,
     * el generador mide el panel y el consumo mide la batería.
     */
    private static function electricalCharacteristics(): Section
    {
        return Section::make('Características eléctricas')
            ->description(
                'Los datos de catálogo de lo que hay conectado en este canal. '
                .'Para calcular los vatios se usa siempre la tensión que el aparato mida '
                .'y mande en cada lectura; lo que se ponga aquí sólo se usa cuando esa lectura llega sin tensión.'
            )
            ->columns(2)
            ->columnSpanFull()
            ->collapsed()
            ->schema([
                TextInput::make('nominal_voltage')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label('Tensión nominal')
                    ->helperText(
                        'La tensión de trabajo de lo que hay conectado en ESTE canal, no la del aparato entero. '
                        .'Un controlador solar tiene tres tensiones distintas a la vez: el panel puede ir a 24 V '
                        .'mientras la batería y la salida de carga van a 12 V, así que cada papel lleva la suya. '
                        .'Se usa para dos cosas: calcular los vatios cuando una lectura llega sin tensión, '
                        .'y repartir los amperios-hora entre el panel y la batería.'
                    ),
                TextInput::make('rated_power_w')
                    ->numeric()->step(0.01)->suffix(' W')
                    ->label('Potencia nominal')
                    ->placeholder('Opcional')
                    ->helperText(
                        'Los vatios que pone el fabricante: los del panel, los que consume el aparato conectado. '
                        .'Es informativo y no entra en ningún cálculo; sirve para saber de un vistazo '
                        .'si lo que se está midiendo cuadra con lo que debería dar.'
                    ),
                TextInput::make('voltage_min')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label(fn (Get $get): string => self::isBattery($get) ? 'Tensión con la batería vacía' : 'Tensión mínima normal')
                    ->placeholder('Opcional')
                    ->helperText(fn (Get $get): string => self::isBattery($get)
                        ? 'La tensión que marca el banco cuando está descargado del todo. Con ésta y la de batería llena '
                          .'se calcula el porcentaje de carga en las lecturas que lleguen sin él. '
                          .'Pon las de tu banco: un LiFePO4 de 12 V va de 10,0 a 14,6 V y uno de plomo de 10,5 a 12,8 V. '
                          .'Un rango inventado da porcentajes inventados.'
                        : 'Por debajo de esta tensión, la lectura se marca como sospechosa: se guarda igual y no se pierde nada, '
                          .'pero deja de contar para las medias y el aparato recibe un aviso en la respuesta de la subida. '
                          .'Sirve para que un sensor que empieza a fallar no arrastre las estadísticas. '
                          .'Déjalo vacío y no se avisará nunca.'),
                TextInput::make('voltage_max')
                    ->numeric()->step(0.01)->suffix(' V')
                    ->label(fn (Get $get): string => self::isBattery($get) ? 'Tensión con la batería llena' : 'Tensión máxima normal')
                    ->placeholder('Opcional')
                    ->helperText(fn (Get $get): string => self::isBattery($get)
                        ? 'La tensión que marca el banco recién cargado, en absorción. Es el otro extremo del cálculo del porcentaje.'
                        : 'Igual que la mínima pero por arriba: una lectura que la supere se guarda y se marca como sospechosa.'),

                // Sólo tienen sentido en una batería.
                TextInput::make('capacity_ah')
                    ->numeric()->step(0.001)->suffix(' Ah')
                    ->label('Capacidad del banco')
                    ->placeholder('Opcional')
                    ->helperText(
                        'Los amperios-hora que pone el fabricante en la batería. '
                        .'Una de 250 Ah a 12 V almacena 3.000 Wh, y ese número se calcula solo a partir de aquí '
                        .'y de la tensión nominal. Admite decimales para baterías pequeñas: 2,5 Ah son 2.500 mAh.'
                    )
                    ->visible(fn (Get $get): bool => self::isBattery($get)),
                TextInput::make('default_interval_seconds')
                    ->numeric()->minValue(1)->default(60)->required()->suffix(' s')
                    ->label('Cada cuántos segundos sube este canal')
                    ->helperText(
                        'Cuánto tiempo representa cada lectura, y por tanto cuánta energía se apunta con ella: '
                        .'2 A a 12 V durante 300 segundos son 2 Wh. '
                        .'Lo normal es que el aparato lo mande él mismo en cada subida (el campo «duration» del contrato) '
                        .'y entonces esto no se usa; esto es el respaldo para cuando no lo manda. '
                        .'Pon cada cuánto sube de verdad: si sube cada 10 minutos y aquí pone 60, '
                        .'se apuntará la sexta parte de la energía real y nadie se dará cuenta.'
                    ),
                Toggle::make('auto_calculate_history')
                    ->label('Rehacer cada noche el total acumulado')
                    ->helperText(
                        'Actívalo cuando el total de por vida de este canal lo calculamos nosotros sumando sus lecturas: '
                        .'cada madrugada se vuelve a sumar, así que un hueco que se rellene más tarde acaba cuadrando. '
                        .'Apágalo cuando el aparato lleva su propio contador de por vida y lo manda en cada subida, '
                        .'como hace el Renogy Rover: ahí el total es suyo y volver a calcularlo lo estropearía.'
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
