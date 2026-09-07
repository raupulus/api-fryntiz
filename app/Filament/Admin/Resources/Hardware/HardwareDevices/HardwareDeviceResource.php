<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices;

use App\Enums\HardwareLocationTypeEnum;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\CreateHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\EditHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\ListHardwareDevices;
use App\Filament\Components\CurrentImage;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\ScopesToOwner;
use App\Models\Hardware\HardwareDevice;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HardwareDeviceResource extends Resource
{
    use ScopesToOwner;

    protected static ?string $model = HardwareDevice::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Hardware';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Dispositivo';

    protected static ?string $pluralModelLabel = 'Dispositivos';

    /**
     * Columnas del estado que rellena el propio dispositivo por la API.
     *
     * @var list<string>
     */
    private const LECTURAS = [
        'temp', 'voltage', 'battery_level', 'cpu', 'ram', 'disk',
        'uptime', 'ip_local', 'ip_public', 'last_seen_at',
    ];

    /**
     * Una lectura del dispositivo, como tarjeta de sólo lectura.
     *
     * Se oculta cuando el valor es `null`, que es lo normal en un cacharro que
     * no mide esa magnitud: una tarjeta vacía no informa de nada y descoloca la
     * rejilla.
     */
    private static function tarjeta(
        string $campo,
        string $etiqueta,
        string $icono,
        ?string $unidad = null,
    ): TextEntry {
        return TextEntry::make($campo)
            ->label($etiqueta)
            ->icon($icono)
            ->suffix($unidad === null ? null : ' '.$unidad)
            ->placeholder('—')
            ->visible(fn (?HardwareDevice $record): bool => $record?->getAttribute($campo) !== null)
            ->extraAttributes([
                'class' => 'rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900',
            ]);
    }

    /**
     * ¿El dispositivo ha reportado algo alguna vez?
     *
     * Sin ninguna lectura, la sección entera sobra: mejor eso que una fila de
     * huecos en la parte de arriba de la ficha.
     */
    private static function tieneAlgunaLectura(?HardwareDevice $record): bool
    {
        if ($record === null) {
            return false;
        }

        foreach (self::LECTURAS as $campo) {
            if ($record->getAttribute($campo) !== null) {
                return true;
            }
        }

        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Arriba del todo y en tarjetas, no en un formulario: son
                // lecturas que manda el propio cacharro por la API y aquí no se
                // editan. Antes eran diez `TextInput` deshabilitados dentro de
                // una sección colapsada al final de la página, o sea lectura
                // disfrazada de formulario y encima escondida.
                //
                // Cada tarjeta se oculta si su valor es `null`: un dispositivo
                // que no mide CPU no tiene por qué enseñar un hueco vacío.
                Section::make('Estado del dispositivo')
                    ->description('Último estado conocido, reportado por el propio dispositivo a través de la API. Solo lectura.')
                    ->icon(Heroicon::OutlinedSignal)
                    ->columns(['default' => 2, 'sm' => 3, 'xl' => 5])
                    ->schema([
                        self::tarjeta('temp', 'Temperatura', 'heroicon-o-fire', '°C'),
                        self::tarjeta('voltage', 'Tensión', 'heroicon-o-bolt', 'V'),
                        self::tarjeta('battery_level', 'Batería', 'heroicon-o-battery-100', '%'),
                        self::tarjeta('cpu', 'CPU', 'heroicon-o-cpu-chip', '%'),
                        self::tarjeta('ram', 'Memoria', 'heroicon-o-circle-stack', '%'),
                        self::tarjeta('disk', 'Disco', 'heroicon-o-server', '%'),

                        // Los segundos son lo que manda el cacharro, pero
                        // «14212800» no dice nada de un vistazo.
                        self::tarjeta('uptime', 'Encendido', 'heroicon-o-clock')
                            ->formatStateUsing(fn ($state): string => self::uptimeLegible((int) $state)),

                        self::tarjeta('ip_local', 'IP local', 'heroicon-o-computer-desktop'),
                        self::tarjeta('ip_public', 'IP pública', 'heroicon-o-globe-alt'),
                        self::tarjeta('last_seen_at', 'Última señal', 'heroicon-o-signal')
                            ->formatStateUsing(fn ($state): string => $state?->diffForHumans() ?? ''),
                    ])
                    ->visible(fn (?HardwareDevice $record): bool => self::tieneAlgunaLectura($record))
                    ->columnSpanFull(),

                // Lo único que sigue siendo un campo de texto: es JSON libre y
                // no cabe en una tarjeta.
                Section::make('Métricas adicionales')
                    ->description('Lo que el dispositivo manda en `extra`, tal cual.')
                    ->collapsed()
                    ->schema([
                        Textarea::make('extra')
                            ->hiddenLabel()
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(6)
                            ->formatStateUsing(fn ($state) => filled($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : null)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?HardwareDevice $record): bool => filled($record?->extra))
                    ->columnSpanFull(),

                Section::make('Imagen principal')
                    ->schema([
                        // La imagen que ya tiene guardada. El uploader de abajo no puede
                        // enseñarla: apunta a `image_id`, una clave foránea, y espera una
                        // ruta de disco (ver `CurrentImage`).
                        CurrentImage::deLaRelacion(),
                        ImageCropperUpload::makeImage('image_id')
                            ->cover16x9()
                            ->storeFiles(false)
                            ->dehydrated(fn ($state) => filled($state))
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
                            ->relationship('type', 'name')
                            ->label('Tipo de hardware'),
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
                        TextInput::make('ip_local')
                            ->label('IP Local')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se actualiza automáticamente en las peticiones a la API.'),
                        TextInput::make('ip_public')
                            ->label('IP Pública')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se actualiza automáticamente en las peticiones a la API.'),
                        DateTimePicker::make('buy_at')->label('Comprado el'),
                        DateTimePicker::make('last_seen_at')
                            ->label('Última vez en línea')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se actualiza automáticamente en las peticiones a la API.'),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                Section::make('Ubicación')
                    ->description('Dónde está el hardware. Para estaciones meteorológicas, elige "Estación Meteorológica" en el tipo de hardware.')
                    ->columns(2)
                    ->schema([
                        Select::make('location_type')
                            ->label('Ubicación')
                            ->options(HardwareLocationTypeEnum::options())
                            ->native(false)
                            ->default(HardwareLocationTypeEnum::Indoor->value)
                            ->selectablePlaceholder(false)
                            ->helperText('Interior o exterior. Por defecto interior.'),
                        TextInput::make('zone')
                            ->label('Zona')
                            ->maxLength(100)
                            ->placeholder('EJ: Azotea, Salón, Jardín'),
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
                TextColumn::make('type.name')
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
                TextColumn::make('location_type')
                    ->label('Ubicación')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof HardwareLocationTypeEnum ? $state->label() : $state)
                    ->color(fn ($state) => $state === HardwareLocationTypeEnum::Outdoor ? 'success' : 'info')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('zone')
                    ->label('Zona')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
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
                    ->relationship('type', 'name')->label('Tipo'),
                SelectFilter::make('location_type')
                    ->label('Ubicación')
                    ->options(HardwareLocationTypeEnum::options()),
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
        // Los tokens primero: Filament abre la primera pestaña, y de las dos
        // ésta es la que se usa a diario.
        return [
            RelationManagers\TokensRelationManager::class,
            RelationManagers\ComponentsRelationManager::class,
        ];
    }

    /**
     * Los segundos de uptime, en unidades que se leen de un vistazo.
     *
     * Se queda en las dos unidades más grandes que apliquen: «3 meses, 12 días»
     * dice lo que hay que saber, y «3 meses, 12 días, 4 horas y 51 minutos» sólo
     * hace la línea más larga.
     */
    private static function uptimeLegible(int $segundos): string
    {
        if ($segundos <= 0) {
            return 'recién arrancado';
        }

        $unidades = [
            'mes' => 2_592_000,
            'día' => 86_400,
            'hora' => 3_600,
            'minuto' => 60,
        ];

        $plurales = ['mes' => 'meses', 'día' => 'días', 'hora' => 'horas', 'minuto' => 'minutos'];
        $partes = [];

        foreach ($unidades as $nombre => $tamanyo) {
            if (count($partes) === 2) {
                break;
            }

            $cantidad = intdiv($segundos, $tamanyo);

            if ($cantidad === 0 && $partes === []) {
                continue;
            }

            if ($cantidad > 0) {
                $partes[] = $cantidad.' '.($cantidad === 1 ? $nombre : $plurales[$nombre]);
                $segundos -= $cantidad * $tamanyo;
            }
        }

        return $partes === [] ? 'menos de un minuto' : implode(', ', $partes);
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
