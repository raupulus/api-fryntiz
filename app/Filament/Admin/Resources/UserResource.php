<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Enums\UserRoleEnum;
use App\Filament\Components\ImageCropperUpload;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action as FormAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Resource Filament para usuarios.
 *
 * Reescrito en fix_10 / fase 01 para resolver el HTTP 500 producido por
 * el uso de Filament\Tables\Actions\* (inexistente en Filament v5).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Roles que puede repartir quien está usando el formulario ahora mismo.
     *
     * Nadie asigna un rol por encima del suyo: el criterio vive en
     * {@see UserRoleEnum::assignableRoles()} y se aplica en dos capas —las
     * opciones que se pintan y la regla de validación— porque acotar sólo las
     * opciones se salta cambiando el `<select>` desde el navegador (AR-P01).
     *
     * Sin usuario en sesión no hay nada asignable: así el formulario falla
     * cerrado en lugar de ofrecerlo todo.
     *
     * @return list<int>
     */
    protected static function assignableRoleIds(): array
    {
        return auth()->user()?->assignableRoleIds() ?? [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Foto de perfil')
                ->schema([
                    ImageCropperUpload::makeImage('profile_photo_path')
                        ->avatar()
                        ->directory('profile-photos')
                        ->extraAttributes(['class' => 'flex justify-center items-center [&_.filepond--root]:mx-auto'])
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ])
                ->extraAttributes(['class' => 'flex justify-center items-center'])
                ->columnSpanFull(),

            Section::make('Perfil')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->label('Nombre'),
                    TextInput::make('surname')->maxLength(255)->label('Apellidos'),
                ])->columnSpanFull(),

            Section::make('Datos de acceso')
                ->columns(2)
                ->schema([
                    TextInput::make('email')
                        ->email()->required()->maxLength(255)
                        ->unique(ignoreRecord: true)
                        // D91: el email no lo cambia nadie sobre sí mismo, ni
                        // siquiera un administrador. Cambiarse el propio email
                        // es la vía clásica para apropiarse de una cuenta.
                        ->disabled(fn ($record) => $record?->id === auth()->id())
                        ->dehydrated(fn ($record) => $record?->id !== auth()->id())
                        ->helperText(fn ($record) => $record?->id === auth()->id()
                            ? 'No puedes cambiar tu propio email.'
                            : null)
                        ->label('Email'),
                    TextInput::make('nickname')
                        ->maxLength(511)
                        ->unique(ignoreRecord: true)
                        ->label('Nickname'),
                    TextInput::make('password')
                        ->password()->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(Password::defaults())
                        ->label('Contraseña'),
                    // AR-P01: las opciones se acotan a lo que quien edita puede
                    // repartir. Sin esto, el `Select` ofrecía los cuatro roles a
                    // cualquiera que llegase al formulario, así que un `Admin`
                    // podía ponerse `SuperAdmin` —o dar de alta uno nuevo con
                    // una contraseña elegida por él— y quedarse con el bypass
                    // total de `Gate::before`.
                    //
                    // El filtro del `modifyQueryUsing` es lo que ve el
                    // navegador; la regla `in` de abajo es lo que impide que
                    // valga de nada trastear el `<select>` desde las
                    // herramientas de desarrollo. Las dos, no una.
                    Select::make('role_id')
                        ->relationship(
                            'role',
                            'display_name',
                            fn (Builder $query) => $query->whereIn('id', self::assignableRoleIds()),
                        )
                        ->rule(Rule::in(self::assignableRoleIds()))
                        ->required()->preload()->searchable()
                        ->live()
                        ->label('Rol'),
                    Toggle::make('is_active')
                        ->default(true)
                        ->label('Cuenta activa')
                        ->helperText('Desactivar corta el acceso y anula todos los tokens del usuario, incluidos los de sus dispositivos, sin borrar nada.'),
                ])->columnSpanFull(),

            Section::make('Plataformas que puede editar')
                ->description('Sólo aplica al rol Editor: limita el contenido que puede tocar. Un administrador llega a todas y no necesita marcar ninguna.')
                ->schema([
                    Select::make('platforms')
                        ->relationship('platforms', 'name')
                        ->multiple()->preload()->searchable()
                        ->hiddenLabel(),
                ])
                ->visible(fn ($get) => (int) $get('role_id') === UserRoleEnum::Editor->value)
                ->columnSpanFull(),

            Section::make('Detalles')
                ->relationship('details')
                ->columns(2)
                ->schema([
                    TextInput::make('profession')->maxLength(255)->label('Profesión'),
                    TextInput::make('web')->url()->maxLength(255)->label('Web personal'),
                ])->columnSpanFull(),

            Section::make('Notificaciones')
                ->relationship('settings')
                ->columns(3)
                ->schema([
                    Toggle::make('send_email')->default(true)->label('Permitir emails'),
                    Toggle::make('send_notification')->default(true)->label('Notificaciones'),
                    Toggle::make('send_notification_push')->default(true)->label('Push'),
                ])
                ->columnSpanFull(),

            Section::make('Verificación')
                ->schema([
                    Toggle::make('is_email_verified')
                        ->label('Email verificado')
                        ->helperText('Indica si el email del usuario ha sido verificado.')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Toggle $component, $record) {
                            $component->state($record?->email_verified_at !== null);
                        })
                        ->disabled(fn ($record) => $record?->email_verified_at !== null),

                    Actions::make([
                        FormAction::make('verify_email')
                            ->label('Verificar email ahora')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('¿Verificar email?')
                            ->modalDescription('Esta acción no se puede deshacer. Se marcará el email como verificado con la fecha y hora actuales.')
                            ->modalSubmitActionLabel('Sí, verificar')
                            ->action(function ($record, $set) {
                                $record->update(['email_verified_at' => now()]);
                                $set('is_email_verified', true);
                            })
                            ->visible(fn ($record) => $record && $record->email_verified_at === null),
                    ]),
                ])
                ->columnSpanFull()
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_photo_path')->circular()->disk('public')->label('Foto'),
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('nickname')->searchable()->toggleable()->label('Nick'),
                TextColumn::make('email')->searchable()->copyable()->label('Email'),
                TextColumn::make('role.display_name')->badge()->label('Rol'),
                IconColumn::make('email_verified_at')->boolean()->label('Verif.'),
                IconColumn::make('is_active')->boolean()->label('Activo'),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->label('Creado'),
            ])
            ->filters([
                SelectFilter::make('role_id')->relationship('role', 'display_name')->label('Rol'),
                TrashedFilter::make(),
                TernaryFilter::make('email_verified_at')->nullable()->label('Verificado'),
                TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\SocialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'create' => UserResource\Pages\CreateUser::route('/create'),
            'edit' => UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
