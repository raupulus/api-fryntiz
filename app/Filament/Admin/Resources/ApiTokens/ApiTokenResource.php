<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens;

use App\Filament\Admin\Resources\ApiTokens\Pages\CreateApiToken;
use App\Filament\Admin\Resources\ApiTokens\Pages\ListApiTokens;
use App\Models\ApiToken;
use App\Models\Hardware\HardwareDevice;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApiTokenResource extends Resource
{
    protected static ?string $model = ApiToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'API Token';

    protected static ?string $pluralModelLabel = 'API Tokens';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nuevo token')->columns(1)->visibleOn('create')->schema([
                Select::make('tokenable_id')
                    ->label('Usuario')
                    ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->required()->columnSpanFull(),
                Hidden::make('tokenable_type')->default(User::class),
                TextInput::make('name')->required()->maxLength(255)
                    ->helperText('Identifica el contexto donde se usará el token.')
                    ->label('Nombre del token')->columnSpanFull(),
                // Las opciones salen del catálogo real de abilities. Antes eran
                // una lista escrita a mano con nombres inventados
                // ("weather:write", "content:read") que no exige ninguna ruta,
                // y venía marcado "*" por defecto: cada token emitido desde
                // aquí podía absolutamente todo.
                CheckboxList::make('abilities')
                    ->options(TokenAbilities::MODULE_ABILITIES)
                    ->columns(2)
                    ->required()
                    ->label('Permisos de módulo')
                    ->helperText('No existe un permiso comodín. Marca sólo lo que el cacharro necesite escribir.')
                    ->columnSpanFull(),
                Select::make('device_id')
                    ->label('Ligar a un dispositivo')
                    ->options(fn ($get) => $get('tokenable_id')
                        ? HardwareDevice::query()
                            ->where('user_id', $get('tokenable_id'))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                        : [])
                    ->searchable()
                    ->required()
                    ->helperText('Añade la ability "device:{id}". Sin ella el token escribiría en cualquier dispositivo del mismo dueño.')
                    ->columnSpanFull(),
                DateTimePicker::make('expires_at')->seconds()
                    ->label('Expira el (opcional)')
                    ->helperText('Los tokens de dispositivos IoT se dejan sin caducidad a propósito: no se sube a la montaña a reflashear un token. La seguridad la dan los permisos de arriba.')
                    ->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_name')->label('Usuario')->searchable(),
                TextColumn::make('name')->searchable()->label('Nombre'),
                TextColumn::make('abilities')->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->limit(40)->label('Scopes')->toggleable(),
                TextColumn::make('last_used_at')->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')->label('Último uso'),
                TextColumn::make('expires_at')->dateTime('d/m/Y H:i')
                    ->placeholder('No expira')
                    ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : 'gray')
                    ->label('Expira'),
                TextColumn::make('created_at')->dateTime('d/m/Y')->sortable()->label('Creado'),
            ])
            ->filters([
                SelectFilter::make('tokenable_id')
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->label('Usuario'),
                Filter::make('expired')
                    ->query(fn ($q) => $q->whereNotNull('expires_at')->where('expires_at', '<', now()))
                    ->label('Expirados'),
                Filter::make('unused')
                    ->query(fn ($q) => $q->whereNull('last_used_at'))->label('Sin usar'),
            ])
            ->recordActions([
                DeleteAction::make()->label('Revocar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Revocar seleccionados'),
                    BulkAction::make('revoke_user')
                        ->icon('heroicon-o-shield-exclamation')->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $users = $records->pluck('tokenable_id')->unique();
                            ApiToken::whereIn('tokenable_id', $users)
                                ->where('tokenable_type', User::class)->delete();
                            Notification::make()->title('Tokens del usuario revocados')->success()->send();
                        })->label('Revocar TODOS del usuario'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiTokens::route('/'),
            'create' => CreateApiToken::route('/create'),
        ];
    }
}
