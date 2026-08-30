<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ApiTokens;

use App\Filament\Tenant\Resources\ApiTokens\Pages\CreateApiToken;
use App\Filament\Tenant\Resources\ApiTokens\Pages\ListApiTokens;
use App\Models\ApiToken;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Los tokens de hardware del propio usuario.
 *
 * Es la otra mitad de lo que faltaba del panel `tenant` (D90): «un panel para
 * usuarios que solo pueden editar sus datos personales y tokens de hardware».
 *
 * Dos cosas que este recurso hace y que en su día se olvidaron en sitios
 * parecidos:
 *
 *  1. **Aísla.** `getEloquentQuery()` filtra por `tokenable_id`. El panel deja
 *     entrar a cualquier usuario autenticado, así que sin ese filtro cada uno
 *     vería —y podría revocar— los tokens de los demás. Es el mismo error que
 *     N15 y A3.
 *  2. **No emite comodines.** Las opciones salen del catálogo de abilities y
 *     hay que ligar el token a un dispositivo. Un usuario creándose un token
 *     desde su panel no puede acabar con un `*`.
 */
class ApiTokenResource extends Resource
{
    protected static ?string $model = ApiToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Dispositivos';

    protected static ?string $modelLabel = 'Token de hardware';

    protected static ?string $pluralModelLabel = 'Tokens de hardware';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Sólo los tokens del usuario que está mirando.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nuevo token')->columns(1)->visibleOn('create')->schema([
                TextInput::make('name')
                    ->required()->maxLength(255)
                    ->label('Nombre')
                    ->helperText('Para reconocerlo después: «estación de la azotea», «rover del huerto»…'),

                Select::make('device_id')
                    ->label('Dispositivo')
                    ->options(fn () => HardwareDevice::query()
                        ->where('user_id', auth()->id())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()->required()
                    ->helperText('El token queda ligado a este dispositivo y no sirve para ningún otro.'),

                CheckboxList::make('abilities')
                    ->options(TokenAbilities::MODULE_ABILITIES)
                    ->columns(2)
                    ->required()
                    ->label('Permisos')
                    ->helperText('No existe un permiso comodín. Marca sólo lo que el cacharro necesite escribir.'),

                DateTimePicker::make('expires_at')
                    ->seconds(false)
                    ->label('Caduca el (opcional)')
                    ->helperText('Los tokens de dispositivos se dejan sin caducidad a propósito: no se sube a la montaña a reflashear un token. La seguridad la dan los permisos.'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->label('Nombre'),
                TextColumn::make('abilities')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->limit(50)->label('Permisos')->wrap(),
                TextColumn::make('last_used_at')->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')->label('Último uso'),
                TextColumn::make('expires_at')->dateTime('d/m/Y H:i')
                    ->placeholder('No caduca')
                    ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : 'gray')
                    ->label('Caduca'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->sortable()->label('Creado'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                // Sólo ver y revocar: un token no se edita, se revoca y se
                // emite otro. Cambiarle los permisos a uno que ya está dentro
                // de un cacharro es la forma de perder el rastro de qué puede
                // hacer cada cual.
                DeleteAction::make()->label('Revocar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Revocar'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiTokens::route('/'),
            'create' => CreateApiToken::route('/create'),
        ];
    }
}
