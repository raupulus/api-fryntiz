<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Hardware\HardwareDevices;

use App\Enums\HardwareLocationTypeEnum;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\CreateHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\EditHardwareDevice;
use App\Filament\Admin\Resources\Hardware\HardwareDevices\Pages\ListHardwareDevices;
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
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
    private const READING_FIELDS = [
        'temp', 'voltage', 'battery_level', 'battery_voltage', 'cpu', 'ram', 'disk',
        'uptime', 'ip_local', 'ip_public', 'last_seen_at',
    ];

    /**
     * Una lectura del dispositivo, como tarjeta de sólo lectura.
     *
     * Se oculta cuando el valor es `null`, que es lo normal en un cacharro que
     * no mide esa magnitud: una tarjeta vacía no informa de nada y descoloca la
     * rejilla.
     */
    private static function readingCard(
        string $field,
        string $label,
        string $icon,
        ?string $unit = null,
    ): TextEntry {
        return TextEntry::make($field)
            ->label($label)
            ->icon($icon)
            ->suffix($unit === null ? null : ' '.$unit)
            ->placeholder('—')
            ->visible(fn (?HardwareDevice $record): bool => $record?->getAttribute($field) !== null)
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
    private static function hasAnyReading(?HardwareDevice $record): bool
    {
        if ($record === null) {
            return false;
        }

        foreach (self::READING_FIELDS as $field) {
            if ($record->getAttribute($field) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Una tarjeta tipo badge por cada clave del `extra` del dispositivo, una
     * al lado de otra y saltando de línea cuando no caben más (clase
     * `hd-extra` en `panel.css`, que fuerza el `flex-wrap` sobre el `Flex`
     * de Filament).
     *
     * @return list<Flex>
     */
    private static function extraBadges(?HardwareDevice $record): array
    {
        $extra = $record?->extra;

        if (! is_array($extra)) {
            return [];
        }

        $badges = collect($extra)
            ->map(fn ($value, $key) => TextEntry::make("extra.{$key}")
                ->label((string) $key)
                ->state(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                ->badge())
            ->values()
            ->all();

        return [
            Flex::make($badges)->extraAttributes(['class' => 'hd-extra']),
        ];
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
                        self::readingCard('temp', 'Temperatura', 'heroicon-o-fire', '°C'),
                        self::readingCard('voltage', 'Tensión', 'heroicon-o-bolt', 'V'),
                        self::readingCard('battery_level', 'Batería', 'heroicon-o-battery-100', '%'),
                        self::readingCard('battery_voltage', 'Tensión de batería', 'heroicon-o-bolt', 'V'),
                        self::readingCard('cpu', 'CPU', 'heroicon-o-cpu-chip', '%'),
                        self::readingCard('ram', 'Memoria', 'heroicon-o-circle-stack', '%'),
                        self::readingCard('disk', 'Disco', 'heroicon-o-server', '%'),

                        // Los segundos son lo que manda el cacharro, pero
                        // «14212800» no dice nada de un vistazo.
                        self::readingCard('uptime', 'Encendido', 'heroicon-o-clock')
                            ->formatStateUsing(fn ($state): string => self::humanReadableUptime((int) $state)),

                        self::readingCard('ip_local', 'IP local', 'heroicon-o-computer-desktop'),
                        self::readingCard('ip_public', 'IP pública', 'heroicon-o-globe-alt'),
                        self::readingCard('last_seen_at', 'Última señal', 'heroicon-o-signal')
                            ->formatStateUsing(fn ($state): string => $state?->diffForHumans() ?? ''),
                    ])
                    ->visible(fn (?HardwareDevice $record): bool => self::hasAnyReading($record))
                    ->columnSpanFull(),

                // Una tarjeta tipo badge por cada clave de `extra`. Si el JSON
                // está vacío la sección entera no se muestra.
                Section::make('Métricas adicionales')
                    ->description('Lo que el dispositivo manda en `extra`, tal cual.')
                    ->schema(fn (?HardwareDevice $record) => self::extraBadges($record))
                    ->visible(fn (?HardwareDevice $record): bool => filled($record?->extra))
                    ->columnSpanFull(),

                Section::make('Imagen principal')
                    ->schema([
                        ImageCropperUpload::makeImage('image_id')
                            ->asFileRecord()
                            ->cover16x9()
                            ->storeFiles(false)
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
                            ->integer()
                            ->label('Capacidad nominal de batería')
                            ->helperText('En mAh, sin decimales. EJ: 4200.'),
                        TextInput::make('battery_nominal_voltage')
                            ->numeric()
                            ->label('Tensión nominal de batería')
                            ->helperText('Tensión de diseño que declara el fabricante, EJ: 12. No es una medida.'),
                        TextInput::make('url_company')->label('Sitio web empresa'),
                        TextInput::make('ip_local')
                            ->label('IP Local')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Se actualiza automáticamente en las peticiones a la API.'),
                        DateTimePicker::make('buy_at')->label('Comprado el'),
                        Toggle::make('notify_on_silence')
                            ->label('Avisar si deja de reportar')
                            ->helperText('Desactívalo en hardware que enciendes de forma esporádica a propósito, para que "iot:check-silent-devices" no lo marque como mudo cada día.')
                            ->default(true),
                        Toggle::make('is_public')
                            ->label('Visible públicamente')
                            ->helperText('Si se activa, el dispositivo será visible en el catálogo público web de hardware.')
                            ->default(false),
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
                TextColumn::make('battery_nominal_voltage')
                    ->label('Tensión nominal batería')
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
                IconColumn::make('notify_on_silence')
                    ->label('Avisa si se calla')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
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
                TernaryFilter::make('notify_on_silence')
                    ->label('Avisa si se calla'),
                TernaryFilter::make('is_public')
                    ->label('Público'),
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
            RelationManagers\EnergyRelationManager::class,
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
    private static function humanReadableUptime(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'recién arrancado';
        }

        $unitSeconds = [
            'mes' => 2_592_000,
            'día' => 86_400,
            'hora' => 3_600,
            'minuto' => 60,
        ];

        $unitPlurals = ['mes' => 'meses', 'día' => 'días', 'hora' => 'horas', 'minuto' => 'minutos'];
        $parts = [];

        foreach ($unitSeconds as $unit => $unitSize) {
            if (count($parts) === 2) {
                break;
            }

            $amount = intdiv($seconds, $unitSize);

            if ($amount === 0 && $parts === []) {
                continue;
            }

            if ($amount > 0) {
                $parts[] = $amount.' '.($amount === 1 ? $unit : $unitPlurals[$unit]);
                $seconds -= $amount * $unitSize;
            }
        }

        return $parts === [] ? 'menos de un minuto' : implode(', ', $parts);
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
